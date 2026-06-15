-- =========================================================
-- Mentup - schema.sql
-- PostgreSQL / Supabase
-- Sistema privado para campeonatos de Vôlei de Praia
-- =========================================================

create extension if not exists pgcrypto;

-- =========================================================
-- ENUMS
-- =========================================================

create type public.championship_status as enum (
  'in_progress',
  'finished'
);

create type public.tournament_format as enum (
  'groups_and_knockout',
  'knockout',
  'round_robin'
);

create type public.team_mode as enum (
  'duo',
  'quartet'
);

create type public.team_member_role as enum (
  'starter',
  'reserve'
);

create type public.phase_type as enum (
  'group_stage',
  'knockout',
  'round_robin'
);

create type public.match_best_of as enum (
  'best_of_1',
  'best_of_3'
);

create type public.match_status as enum (
  'scheduled',
  'completed'
);

-- =========================================================
-- FUNÇÕES BASE
-- =========================================================

create or replace function public.set_updated_at()
returns trigger
language plpgsql
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

create or replace function public.set_championship_finished_at()
returns trigger
language plpgsql
as $$
begin
  if new.status = 'finished' then
    new.finished_at = coalesce(new.finished_at, now());
  else
    new.finished_at = null;
  end if;

  return new;
end;
$$;

-- =========================================================
-- PROFILES
-- =========================================================

create table public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,

  display_name text not null
    check (char_length(trim(display_name)) between 2 and 80),

  avatar_url text,

  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create trigger trg_profiles_updated_at
before update on public.profiles
for each row
execute function public.set_updated_at();

create or replace function public.handle_new_user()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  insert into public.profiles (
    id,
    display_name,
    avatar_url
  )
  values (
    new.id,
    coalesce(
      nullif(new.raw_user_meta_data ->> 'full_name', ''),
      nullif(new.raw_user_meta_data ->> 'name', ''),
      split_part(new.email, '@', 1),
      'Usuário'
    ),
    new.raw_user_meta_data ->> 'avatar_url'
  )
  on conflict (id) do nothing;

  return new;
end;
$$;

create trigger on_auth_user_created
after insert on auth.users
for each row
execute function public.handle_new_user();

-- =========================================================
-- CAMPEONATOS
-- =========================================================

create table public.championships (
  id uuid primary key default gen_random_uuid(),

  owner_id uuid not null references auth.users(id) on delete cascade,

  name text not null
    check (char_length(trim(name)) between 3 and 100),

  description text,

  -- Caminho privado no Supabase Storage.
  -- Exemplo:
  -- <owner_id>/<championship_id>/foto.png
  photo_path text,

  team_mode public.team_mode not null,

  format public.tournament_format not null,

  best_of public.match_best_of not null default 'best_of_3',

  status public.championship_status not null default 'in_progress',

  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  finished_at timestamptz,

  constraint championships_photo_path_owner_folder_chk
    check (
      photo_path is null
      or photo_path like owner_id::text || '/%'
    ),

  constraint championships_finished_at_chk
    check (
      (status = 'in_progress' and finished_at is null)
      or
      (status = 'finished' and finished_at is not null)
    ),

  constraint championships_id_owner_unique
    unique (id, owner_id)
);

create unique index championships_owner_name_unique
on public.championships (owner_id, lower(trim(name)));

create index championships_owner_status_idx
on public.championships (owner_id, status);

create trigger trg_championships_finished_at
before insert or update on public.championships
for each row
execute function public.set_championship_finished_at();

create trigger trg_championships_updated_at
before update on public.championships
for each row
execute function public.set_updated_at();

-- Congela campeonato finalizado.
-- Única alteração permitida quando finished:
-- status = 'in_progress'
create or replace function public.prevent_finished_championship_changes()
returns trigger
language plpgsql
as $$
begin
  if old.status = 'finished' then

    if new.status = 'in_progress'
       and (
         to_jsonb(new) - 'status' - 'updated_at' - 'finished_at'
       ) = (
         to_jsonb(old) - 'status' - 'updated_at' - 'finished_at'
       )
    then
      return new;
    end if;

    raise exception
      'Campeonato finalizado está congelado. Reabra o campeonato para editar.';
  end if;

  return new;
end;
$$;

create trigger trg_prevent_finished_championship_changes
before update on public.championships
for each row
execute function public.prevent_finished_championship_changes();

-- =========================================================
-- JOGADORES
-- Jogador pertence a um campeonato específico.
-- Não existe UPDATE para jogador.
-- =========================================================

create table public.players (
  id uuid primary key default gen_random_uuid(),

  championship_id uuid not null
    references public.championships(id) on delete cascade,

  name text not null
    check (char_length(trim(name)) between 2 and 80),

  notes text,

  created_at timestamptz not null default now(),

  constraint players_id_championship_unique
    unique (id, championship_id)
);

create unique index players_championship_name_unique
on public.players (championship_id, lower(trim(name)));

create index players_championship_idx
on public.players (championship_id);

-- =========================================================
-- TIMES
-- =========================================================

create table public.teams (
  id uuid primary key default gen_random_uuid(),

  championship_id uuid not null
    references public.championships(id) on delete cascade,

  name text not null
    check (char_length(trim(name)) between 2 and 80),

  seed integer
    check (seed is null or seed > 0),

  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),

  constraint teams_id_championship_unique
    unique (id, championship_id)
);

create unique index teams_championship_name_unique
on public.teams (championship_id, lower(trim(name)));

create index teams_championship_idx
on public.teams (championship_id);

create trigger trg_teams_updated_at
before update on public.teams
for each row
execute function public.set_updated_at();

-- =========================================================
-- MEMBROS DOS TIMES
-- Campeonato em dupla: máximo 2 titulares.
-- Campeonato em quarteto: máximo 4 titulares.
-- Reservas são permitidos.
-- =========================================================

create table public.team_members (
  championship_id uuid not null
    references public.championships(id) on delete cascade,

  team_id uuid not null,

  player_id uuid not null,

  role public.team_member_role not null default 'starter',

  created_at timestamptz not null default now(),

  primary key (team_id, player_id),

  constraint team_members_team_same_championship_fk
    foreign key (team_id, championship_id)
    references public.teams(id, championship_id)
    on delete cascade,

  constraint team_members_player_same_championship_fk
    foreign key (player_id, championship_id)
    references public.players(id, championship_id)
    on delete cascade,

  constraint team_members_player_once_per_championship_unique
    unique (championship_id, player_id)
);

create index team_members_championship_idx
on public.team_members (championship_id);

create index team_members_team_idx
on public.team_members (team_id);

create or replace function public.validate_team_member_limit()
returns trigger
language plpgsql
as $$
declare
  v_team_mode public.team_mode;
  v_max_starters integer;
  v_current_starters integer;
begin
  select c.team_mode
  into v_team_mode
  from public.championships c
  where c.id = new.championship_id;

  if v_team_mode is null then
    raise exception 'Campeonato não encontrado.';
  end if;

  v_max_starters :=
    case v_team_mode
      when 'duo' then 2
      when 'quartet' then 4
    end;

  if new.role = 'starter' then

    if tg_op = 'UPDATE' then
      select count(*)
      into v_current_starters
      from public.team_members tm
      where tm.team_id = new.team_id
        and tm.role = 'starter'
        and not (
          tm.team_id = old.team_id
          and tm.player_id = old.player_id
        );
    else
      select count(*)
      into v_current_starters
      from public.team_members tm
      where tm.team_id = new.team_id
        and tm.role = 'starter';
    end if;

    if v_current_starters >= v_max_starters then
      raise exception 'Limite de titulares excedido para este campeonato.';
    end if;

  end if;

  return new;
end;
$$;

create trigger trg_validate_team_member_limit
before insert or update on public.team_members
for each row
execute function public.validate_team_member_limit();

-- =========================================================
-- FASES
-- =========================================================

create table public.phases (
  id uuid primary key default gen_random_uuid(),

  championship_id uuid not null
    references public.championships(id) on delete cascade,

  type public.phase_type not null,

  name text not null
    check (char_length(trim(name)) between 2 and 80),

  phase_order integer not null
    check (phase_order > 0),

  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),

  constraint phases_id_championship_unique
    unique (id, championship_id),

  constraint phases_championship_order_unique
    unique (championship_id, phase_order)
);

create index phases_championship_idx
on public.phases (championship_id);

create trigger trg_phases_updated_at
before update on public.phases
for each row
execute function public.set_updated_at();

create or replace function public.validate_phase_matches_format()
returns trigger
language plpgsql
as $$
declare
  v_format public.tournament_format;
begin
  select c.format
  into v_format
  from public.championships c
  where c.id = new.championship_id;

  if v_format = 'knockout' and new.type <> 'knockout' then
    raise exception 'Campeonato mata-mata direto só permite fase knockout.';
  end if;

  if v_format = 'round_robin' and new.type <> 'round_robin' then
    raise exception 'Campeonato todos contra todos só permite fase round_robin.';
  end if;

  if v_format = 'groups_and_knockout'
     and new.type not in ('group_stage', 'knockout') then
    raise exception 'Campeonato com grupos e mata-mata só permite group_stage e knockout.';
  end if;

  return new;
end;
$$;

create trigger trg_validate_phase_matches_format
before insert or update on public.phases
for each row
execute function public.validate_phase_matches_format();

-- =========================================================
-- GRUPOS
-- Usado principalmente na fase de grupos.
-- Grupos podem ter tamanhos diferentes.
-- =========================================================

create table public.groups (
  id uuid primary key default gen_random_uuid(),

  championship_id uuid not null
    references public.championships(id) on delete cascade,

  phase_id uuid not null,

  name text not null
    check (char_length(trim(name)) between 1 and 40),

  group_order integer not null
    check (group_order > 0),

  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),

  constraint groups_phase_same_championship_fk
    foreign key (phase_id, championship_id)
    references public.phases(id, championship_id)
    on delete cascade,

  constraint groups_id_championship_unique
    unique (id, championship_id),

  constraint groups_phase_order_unique
    unique (phase_id, group_order),

  constraint groups_phase_name_unique
    unique (phase_id, name)
);

create index groups_championship_idx
on public.groups (championship_id);

create index groups_phase_idx
on public.groups (phase_id);

create trigger trg_groups_updated_at
before update on public.groups
for each row
execute function public.set_updated_at();

create table public.group_teams (
  championship_id uuid not null
    references public.championships(id) on delete cascade,

  group_id uuid not null,

  team_id uuid not null,

  position integer
    check (position is null or position > 0),

  created_at timestamptz not null default now(),

  primary key (group_id, team_id),

  constraint group_teams_group_same_championship_fk
    foreign key (group_id, championship_id)
    references public.groups(id, championship_id)
    on delete cascade,

  constraint group_teams_team_same_championship_fk
    foreign key (team_id, championship_id)
    references public.teams(id, championship_id)
    on delete cascade,

  constraint group_teams_team_once_unique
    unique (championship_id, team_id)
);

create index group_teams_championship_idx
on public.group_teams (championship_id);

create index group_teams_group_idx
on public.group_teams (group_id);

-- =========================================================
-- PARTIDAS
-- O chaveamento pode ser calculado na aplicação.
-- A aplicação salva as partidas geradas aqui.
-- =========================================================

create table public.matches (
  id uuid primary key default gen_random_uuid(),

  championship_id uuid not null
    references public.championships(id) on delete cascade,

  phase_id uuid not null,

  group_id uuid,

  team_a_id uuid not null,
  team_b_id uuid not null,

  winner_team_id uuid,

  status public.match_status not null default 'scheduled',

  round_number integer not null default 1
    check (round_number > 0),

  match_order integer not null default 1
    check (match_order > 0),

  scheduled_at timestamptz,
  played_at timestamptz,

  notes text,

  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),

  constraint matches_phase_same_championship_fk
    foreign key (phase_id, championship_id)
    references public.phases(id, championship_id)
    on delete cascade,

  constraint matches_group_same_championship_fk
    foreign key (group_id, championship_id)
    references public.groups(id, championship_id)
    on delete cascade,

  constraint matches_team_a_same_championship_fk
    foreign key (team_a_id, championship_id)
    references public.teams(id, championship_id)
    on delete cascade,

  constraint matches_team_b_same_championship_fk
    foreign key (team_b_id, championship_id)
    references public.teams(id, championship_id)
    on delete cascade,

  constraint matches_winner_team_fk
    foreign key (winner_team_id)
    references public.teams(id)
    on delete set null,

  constraint matches_id_championship_unique
    unique (id, championship_id),

  constraint matches_different_teams_chk
    check (team_a_id <> team_b_id),

  constraint matches_winner_is_participant_chk
    check (
      winner_team_id is null
      or winner_team_id = team_a_id
      or winner_team_id = team_b_id
    ),

  constraint matches_status_winner_chk
    check (
      (status = 'scheduled' and winner_team_id is null and played_at is null)
      or
      (status = 'completed' and winner_team_id is not null and played_at is not null)
    )
);

create index matches_championship_idx
on public.matches (championship_id);

create index matches_phase_idx
on public.matches (phase_id);

create index matches_group_idx
on public.matches (group_id);

create index matches_status_idx
on public.matches (championship_id, status);

create trigger trg_matches_updated_at
before update on public.matches
for each row
execute function public.set_updated_at();

create or replace function public.validate_match_structure()
returns trigger
language plpgsql
as $$
declare
  v_phase_type public.phase_type;
  v_team_a_in_group boolean;
  v_team_b_in_group boolean;
begin
  select p.type
  into v_phase_type
  from public.phases p
  where p.id = new.phase_id
    and p.championship_id = new.championship_id;

  if v_phase_type is null then
    raise exception 'Fase inválida para esta partida.';
  end if;

  if v_phase_type = 'group_stage' and new.group_id is null then
    raise exception 'Partida de fase de grupos precisa ter grupo.';
  end if;

  if v_phase_type <> 'group_stage' and new.group_id is not null then
    raise exception 'Apenas partidas de fase de grupos podem ter group_id.';
  end if;

  if new.group_id is not null then
    select exists (
      select 1
      from public.group_teams gt
      where gt.group_id = new.group_id
        and gt.team_id = new.team_a_id
        and gt.championship_id = new.championship_id
    )
    into v_team_a_in_group;

    select exists (
      select 1
      from public.group_teams gt
      where gt.group_id = new.group_id
        and gt.team_id = new.team_b_id
        and gt.championship_id = new.championship_id
    )
    into v_team_b_in_group;

    if not v_team_a_in_group or not v_team_b_in_group then
      raise exception 'Os dois times da partida precisam pertencer ao grupo informado.';
    end if;
  end if;

  return new;
end;
$$;

create trigger trg_validate_match_structure
before insert or update on public.matches
for each row
execute function public.validate_match_structure();

-- =========================================================
-- SETS DA PARTIDA
-- Salva placar por set para cálculo de desempate.
-- Set 1 e 2: mínimo 21 pontos.
-- Tie-break/set 3: mínimo 15 pontos.
-- Diferença mínima: 2 pontos.
-- =========================================================

create table public.match_sets (
  id uuid primary key default gen_random_uuid(),

  championship_id uuid not null
    references public.championships(id) on delete cascade,

  match_id uuid not null,

  set_number integer not null
    check (set_number between 1 and 3),

  team_a_points integer not null
    check (team_a_points >= 0 and team_a_points <= 99),

  team_b_points integer not null
    check (team_b_points >= 0 and team_b_points <= 99),

  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),

  constraint match_sets_match_same_championship_fk
    foreign key (match_id, championship_id)
    references public.matches(id, championship_id)
    on delete cascade,

  constraint match_sets_unique_set_per_match
    unique (match_id, set_number),

  constraint match_sets_no_draw_chk
    check (team_a_points <> team_b_points)
);

create index match_sets_championship_idx
on public.match_sets (championship_id);

create index match_sets_match_idx
on public.match_sets (match_id);

create trigger trg_match_sets_updated_at
before update on public.match_sets
for each row
execute function public.set_updated_at();

create or replace function public.validate_match_set_score()
returns trigger
language plpgsql
as $$
declare
  v_best_of public.match_best_of;
  v_winner_points integer;
  v_loser_points integer;
  v_target_points integer;
begin
  select c.best_of
  into v_best_of
  from public.matches m
  join public.championships c on c.id = m.championship_id
  where m.id = new.match_id
    and m.championship_id = new.championship_id;

  if v_best_of is null then
    raise exception 'Partida ou campeonato não encontrado.';
  end if;

  if v_best_of = 'best_of_1' and new.set_number <> 1 then
    raise exception 'Campeonato melhor de 1 permite apenas o set 1.';
  end if;

  v_winner_points := greatest(new.team_a_points, new.team_b_points);
  v_loser_points := least(new.team_a_points, new.team_b_points);

  v_target_points :=
    case
      when new.set_number = 3 then 15
      else 21
    end;

  if v_winner_points < v_target_points then
    raise exception 'Pontuação insuficiente para vencer este set.';
  end if;

  if (v_winner_points - v_loser_points) < 2 then
    raise exception 'O vencedor do set precisa ter pelo menos 2 pontos de diferença.';
  end if;

  return new;
end;
$$;

create trigger trg_validate_match_set_score
before insert or update on public.match_sets
for each row
execute function public.validate_match_set_score();

create or replace function public.sync_match_result_from_sets()
returns trigger
language plpgsql
as $$
declare
  v_match_id uuid;
  v_best_of public.match_best_of;
  v_required_wins integer;
  v_team_a_sets integer;
  v_team_b_sets integer;
  v_winner_team_id uuid;
begin
  v_match_id := coalesce(new.match_id, old.match_id);

  select c.best_of
  into v_best_of
  from public.matches m
  join public.championships c on c.id = m.championship_id
  where m.id = v_match_id;

  v_required_wins :=
    case v_best_of
      when 'best_of_1' then 1
      when 'best_of_3' then 2
    end;

  select
    coalesce(sum(case when ms.team_a_points > ms.team_b_points then 1 else 0 end), 0),
    coalesce(sum(case when ms.team_b_points > ms.team_a_points then 1 else 0 end), 0)
  into
    v_team_a_sets,
    v_team_b_sets
  from public.match_sets ms
  where ms.match_id = v_match_id;

  if v_team_a_sets > v_required_wins or v_team_b_sets > v_required_wins then
    raise exception 'Quantidade de sets inválida para o formato da partida.';
  end if;

  if v_team_a_sets >= v_required_wins and v_team_a_sets > v_team_b_sets then
    select m.team_a_id
    into v_winner_team_id
    from public.matches m
    where m.id = v_match_id;

    update public.matches
    set
      status = 'completed',
      winner_team_id = v_winner_team_id,
      played_at = coalesce(played_at, now()),
      updated_at = now()
    where id = v_match_id;

  elsif v_team_b_sets >= v_required_wins and v_team_b_sets > v_team_a_sets then
    select m.team_b_id
    into v_winner_team_id
    from public.matches m
    where m.id = v_match_id;

    update public.matches
    set
      status = 'completed',
      winner_team_id = v_winner_team_id,
      played_at = coalesce(played_at, now()),
      updated_at = now()
    where id = v_match_id;

  else
    update public.matches
    set
      status = 'scheduled',
      winner_team_id = null,
      played_at = null,
      updated_at = now()
    where id = v_match_id;
  end if;

  return null;
end;
$$;

create trigger trg_sync_match_result_from_sets
after insert or update or delete on public.match_sets
for each row
execute function public.sync_match_result_from_sets();

-- =========================================================
-- VIEW DE CLASSIFICAÇÃO
-- Critérios base:
-- 1. vitórias
-- 2. saldo de sets
-- 3. saldo de pontos
-- 4. confronto direto fica para a aplicação resolver
-- =========================================================

create or replace view public.v_team_standings
with (security_invoker = true)
as
with entries as (
  select
    gt.championship_id,
    g.phase_id,
    gt.group_id,
    gt.team_id
  from public.group_teams gt
  join public.groups g on g.id = gt.group_id

  union

  select
    p.championship_id,
    p.id as phase_id,
    null::uuid as group_id,
    t.id as team_id
  from public.phases p
  join public.teams t on t.championship_id = p.championship_id
  where p.type = 'round_robin'
),
match_team_stats as (
  select
    m.id as match_id,
    m.championship_id,
    m.phase_id,
    m.group_id,
    m.team_a_id as team_id,

    case when m.winner_team_id = m.team_a_id then 1 else 0 end as wins,
    case when m.winner_team_id = m.team_b_id then 1 else 0 end as losses,

    sum(case when ms.team_a_points > ms.team_b_points then 1 else 0 end)::integer as sets_won,
    sum(case when ms.team_a_points < ms.team_b_points then 1 else 0 end)::integer as sets_lost,

    sum(ms.team_a_points)::integer as points_for,
    sum(ms.team_b_points)::integer as points_against
  from public.matches m
  join public.match_sets ms on ms.match_id = m.id
  where m.status = 'completed'
  group by m.id

  union all

  select
    m.id as match_id,
    m.championship_id,
    m.phase_id,
    m.group_id,
    m.team_b_id as team_id,

    case when m.winner_team_id = m.team_b_id then 1 else 0 end as wins,
    case when m.winner_team_id = m.team_a_id then 1 else 0 end as losses,

    sum(case when ms.team_b_points > ms.team_a_points then 1 else 0 end)::integer as sets_won,
    sum(case when ms.team_b_points < ms.team_a_points then 1 else 0 end)::integer as sets_lost,

    sum(ms.team_b_points)::integer as points_for,
    sum(ms.team_a_points)::integer as points_against
  from public.matches m
  join public.match_sets ms on ms.match_id = m.id
  where m.status = 'completed'
  group by m.id
)
select
  e.championship_id,
  e.phase_id,
  e.group_id,
  e.team_id,

  coalesce(count(mts.match_id), 0)::integer as matches_played,

  coalesce(sum(mts.wins), 0)::integer as wins,
  coalesce(sum(mts.losses), 0)::integer as losses,

  coalesce(sum(mts.sets_won), 0)::integer as sets_won,
  coalesce(sum(mts.sets_lost), 0)::integer as sets_lost,

  coalesce(sum(mts.sets_won), 0)::integer
    - coalesce(sum(mts.sets_lost), 0)::integer as sets_balance,

  coalesce(sum(mts.points_for), 0)::integer as points_for,
  coalesce(sum(mts.points_against), 0)::integer as points_against,

  coalesce(sum(mts.points_for), 0)::integer
    - coalesce(sum(mts.points_against), 0)::integer as points_balance
from entries e
left join match_team_stats mts
  on mts.championship_id = e.championship_id
  and mts.phase_id = e.phase_id
  and mts.team_id = e.team_id
  and (
    mts.group_id = e.group_id
    or e.group_id is null
  )
group by
  e.championship_id,
  e.phase_id,
  e.group_id,
  e.team_id;

-- =========================================================
-- RLS HELPERS
-- =========================================================

create or replace function public.is_championship_owner(p_championship_id uuid)
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select exists (
    select 1
    from public.championships c
    where c.id = p_championship_id
      and c.owner_id = auth.uid()
  );
$$;

create or replace function public.is_championship_in_progress(p_championship_id uuid)
returns boolean
language sql
stable
security definer
set search_path = public
as $$
  select exists (
    select 1
    from public.championships c
    where c.id = p_championship_id
      and c.owner_id = auth.uid()
      and c.status = 'in_progress'
  );
$$;

-- =========================================================
-- RLS
-- Tudo privado.
-- Usuário só acessa os próprios dados.
-- Campeonato finalizado fica congelado.
-- =========================================================

alter table public.profiles enable row level security;
alter table public.championships enable row level security;
alter table public.players enable row level security;
alter table public.teams enable row level security;
alter table public.team_members enable row level security;
alter table public.phases enable row level security;
alter table public.groups enable row level security;
alter table public.group_teams enable row level security;
alter table public.matches enable row level security;
alter table public.match_sets enable row level security;

-- PROFILES

create policy "Users can view own profile"
on public.profiles
for select
to authenticated
using (id = auth.uid());

create policy "Users can insert own profile"
on public.profiles
for insert
to authenticated
with check (id = auth.uid());

create policy "Users can update own profile"
on public.profiles
for update
to authenticated
using (id = auth.uid())
with check (id = auth.uid());

-- CHAMPIONSHIPS

create policy "Users can view own championships"
on public.championships
for select
to authenticated
using (owner_id = auth.uid());

create policy "Users can create own championships"
on public.championships
for insert
to authenticated
with check (owner_id = auth.uid());

create policy "Users can update own championships"
on public.championships
for update
to authenticated
using (owner_id = auth.uid())
with check (owner_id = auth.uid());

create policy "Users can delete only in progress championships"
on public.championships
for delete
to authenticated
using (
  owner_id = auth.uid()
  and status = 'in_progress'
);

-- PLAYERS
-- Sem policy de UPDATE propositalmente.

create policy "Users can view own championship players"
on public.players
for select
to authenticated
using (public.is_championship_owner(championship_id));

create policy "Users can create players only in progress championships"
on public.players
for insert
to authenticated
with check (public.is_championship_in_progress(championship_id));

create policy "Users can delete players only in progress championships"
on public.players
for delete
to authenticated
using (public.is_championship_in_progress(championship_id));

-- TEAMS

create policy "Users can view own championship teams"
on public.teams
for select
to authenticated
using (public.is_championship_owner(championship_id));

create policy "Users can create teams only in progress championships"
on public.teams
for insert
to authenticated
with check (public.is_championship_in_progress(championship_id));

create policy "Users can update teams only in progress championships"
on public.teams
for update
to authenticated
using (public.is_championship_in_progress(championship_id))
with check (public.is_championship_in_progress(championship_id));

create policy "Users can delete teams only in progress championships"
on public.teams
for delete
to authenticated
using (public.is_championship_in_progress(championship_id));

-- TEAM MEMBERS

create policy "Users can view own championship team members"
on public.team_members
for select
to authenticated
using (public.is_championship_owner(championship_id));

create policy "Users can create team members only in progress championships"
on public.team_members
for insert
to authenticated
with check (public.is_championship_in_progress(championship_id));

create policy "Users can update team members only in progress championships"
on public.team_members
for update
to authenticated
using (public.is_championship_in_progress(championship_id))
with check (public.is_championship_in_progress(championship_id));

create policy "Users can delete team members only in progress championships"
on public.team_members
for delete
to authenticated
using (public.is_championship_in_progress(championship_id));

-- PHASES

create policy "Users can view own championship phases"
on public.phases
for select
to authenticated
using (public.is_championship_owner(championship_id));

create policy "Users can create phases only in progress championships"
on public.phases
for insert
to authenticated
with check (public.is_championship_in_progress(championship_id));

create policy "Users can update phases only in progress championships"
on public.phases
for update
to authenticated
using (public.is_championship_in_progress(championship_id))
with check (public.is_championship_in_progress(championship_id));

create policy "Users can delete phases only in progress championships"
on public.phases
for delete
to authenticated
using (public.is_championship_in_progress(championship_id));

-- GROUPS

create policy "Users can view own championship groups"
on public.groups
for select
to authenticated
using (public.is_championship_owner(championship_id));

create policy "Users can create groups only in progress championships"
on public.groups
for insert
to authenticated
with check (public.is_championship_in_progress(championship_id));

create policy "Users can update groups only in progress championships"
on public.groups
for update
to authenticated
using (public.is_championship_in_progress(championship_id))
with check (public.is_championship_in_progress(championship_id));

create policy "Users can delete groups only in progress championships"
on public.groups
for delete
to authenticated
using (public.is_championship_in_progress(championship_id));

-- GROUP TEAMS

create policy "Users can view own championship group teams"
on public.group_teams
for select
to authenticated
using (public.is_championship_owner(championship_id));

create policy "Users can create group teams only in progress championships"
on public.group_teams
for insert
to authenticated
with check (public.is_championship_in_progress(championship_id));

create policy "Users can update group teams only in progress championships"
on public.group_teams
for update
to authenticated
using (public.is_championship_in_progress(championship_id))
with check (public.is_championship_in_progress(championship_id));

create policy "Users can delete group teams only in progress championships"
on public.group_teams
for delete
to authenticated
using (public.is_championship_in_progress(championship_id));

-- MATCHES

create policy "Users can view own championship matches"
on public.matches
for select
to authenticated
using (public.is_championship_owner(championship_id));

create policy "Users can create matches only in progress championships"
on public.matches
for insert
to authenticated
with check (public.is_championship_in_progress(championship_id));

create policy "Users can update matches only in progress championships"
on public.matches
for update
to authenticated
using (public.is_championship_in_progress(championship_id))
with check (public.is_championship_in_progress(championship_id));

create policy "Users can delete matches only in progress championships"
on public.matches
for delete
to authenticated
using (public.is_championship_in_progress(championship_id));

-- MATCH SETS

create policy "Users can view own championship match sets"
on public.match_sets
for select
to authenticated
using (public.is_championship_owner(championship_id));

create policy "Users can create match sets only in progress championships"
on public.match_sets
for insert
to authenticated
with check (public.is_championship_in_progress(championship_id));

create policy "Users can update match sets only in progress championships"
on public.match_sets
for update
to authenticated
using (public.is_championship_in_progress(championship_id))
with check (public.is_championship_in_progress(championship_id));

create policy "Users can delete match sets only in progress championships"
on public.match_sets
for delete
to authenticated
using (public.is_championship_in_progress(championship_id));

-- =========================================================
-- STORAGE PRIVADO PARA FOTO DO CAMPEONATO
-- Bucket: championship-photos
-- Path recomendado:
-- <auth.uid()>/<championship_id>/foto.png
-- =========================================================

insert into storage.buckets (id, name, public)
values ('championship-photos', 'championship-photos', false)
on conflict (id) do nothing;

create policy "Users can view own championship photos"
on storage.objects
for select
to authenticated
using (
  bucket_id = 'championship-photos'
  and (storage.foldername(name))[1] = auth.uid()::text
);

create policy "Users can upload own championship photos"
on storage.objects
for insert
to authenticated
with check (
  bucket_id = 'championship-photos'
  and (storage.foldername(name))[1] = auth.uid()::text
);

create policy "Users can update own championship photos"
on storage.objects
for update
to authenticated
using (
  bucket_id = 'championship-photos'
  and (storage.foldername(name))[1] = auth.uid()::text
)
with check (
  bucket_id = 'championship-photos'
  and (storage.foldername(name))[1] = auth.uid()::text
);

create policy "Users can delete own championship photos"
on storage.objects
for delete
to authenticated
using (
  bucket_id = 'championship-photos'
  and (storage.foldername(name))[1] = auth.uid()::text
);

-- =========================================================
-- GRANTS
-- RLS continua filtrando tudo.
-- =========================================================

grant usage on schema public to authenticated;

grant select, insert, update, delete on public.profiles to authenticated;
grant select, insert, update, delete on public.championships to authenticated;

grant select, insert, delete on public.players to authenticated;

grant select, insert, update, delete on public.teams to authenticated;
grant select, insert, update, delete on public.team_members to authenticated;
grant select, insert, update, delete on public.phases to authenticated;
grant select, insert, update, delete on public.groups to authenticated;
grant select, insert, update, delete on public.group_teams to authenticated;
grant select, insert, update, delete on public.matches to authenticated;
grant select, insert, update, delete on public.match_sets to authenticated;

grant select on public.v_team_standings to authenticated;

grant execute on function public.is_championship_owner(uuid) to authenticated;
grant execute on function public.is_championship_in_progress(uuid) to authenticated;