<?php

class ChampionshipStructureModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function getGenerationContext(string $championshipId, string $ownerId): ?array
    {
        $stmt = $this->pdo->prepare("
            select id, owner_id, format, status
            from championships
            where id = :championship_id
            and owner_id = :owner_id
            limit 1
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':owner_id' => $ownerId,
        ]);

        $championship = $stmt->fetch(PDO::FETCH_ASSOC);

        return $championship ?: null;
    }

    public function hasStructure(string $championshipId): bool
    {
        $stmt = $this->pdo->prepare("
            select exists (
                select 1
                from phases
                where championship_id = :championship_id
            )
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        return (bool) $stmt->fetchColumn();
    }

    public function getTeams(string $championshipId): array
    {
        $stmt = $this->pdo->prepare("
            select id, name
            from teams
            where championship_id = :championship_id
            order by coalesce(seed, 2147483647), created_at, name
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPhase(string $championshipId, string $type, string $name, int $phaseOrder): string
    {
        $stmt = $this->pdo->prepare("
            insert into phases (
                championship_id,
                type,
                name,
                phase_order
            ) values (
                :championship_id,
                :type,
                :name,
                :phase_order
            )
            returning id
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':type' => $type,
            ':name' => $name,
            ':phase_order' => $phaseOrder,
        ]);

        return (string) $stmt->fetchColumn();
    }

    public function createGroup(string $championshipId, string $phaseId, string $name, int $groupOrder): string
    {
        $stmt = $this->pdo->prepare("
            insert into groups (
                championship_id,
                phase_id,
                name,
                group_order
            ) values (
                :championship_id,
                :phase_id,
                :name,
                :group_order
            )
            returning id
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':phase_id' => $phaseId,
            ':name' => $name,
            ':group_order' => $groupOrder,
        ]);

        return (string) $stmt->fetchColumn();
    }

    public function addTeamToGroup(string $championshipId, string $groupId, string $teamId, int $position): void
    {
        $stmt = $this->pdo->prepare("
            insert into group_teams (
                championship_id,
                group_id,
                team_id,
                position
            ) values (
                :championship_id,
                :group_id,
                :team_id,
                :position
            )
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':group_id' => $groupId,
            ':team_id' => $teamId,
            ':position' => $position,
        ]);
    }

    public function createMatch(
        string $championshipId,
        string $phaseId,
        ?string $groupId,
        string $teamAId,
        string $teamBId,
        int $roundNumber,
        int $matchOrder,
        ?string $notes = null
    ): void {
        $stmt = $this->pdo->prepare("
            insert into matches (
                championship_id,
                phase_id,
                group_id,
                team_a_id,
                team_b_id,
                round_number,
                match_order,
                notes
            ) values (
                :championship_id,
                :phase_id,
                :group_id,
                :team_a_id,
                :team_b_id,
                :round_number,
                :match_order,
                :notes
            )
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':phase_id' => $phaseId,
            ':group_id' => $groupId,
            ':team_a_id' => $teamAId,
            ':team_b_id' => $teamBId,
            ':round_number' => $roundNumber,
            ':match_order' => $matchOrder,
            ':notes' => $notes,
        ]);
    }

    public function getStructure(string $championshipId): array
    {
        return [
            'groups' => $this->getGroupStandings($championshipId),
            'round_robin' => $this->getRoundRobinStandings($championshipId),
            'knockout_rounds' => $this->getKnockoutRounds($championshipId),
            'has_knockout_phase' => $this->hasPhaseType($championshipId, 'knockout'),
            'recent_matches' => $this->getRecentMatches($championshipId),
            'all_matches' => $this->getRecentMatches($championshipId, null),
            'teams' => $this->getTeamsWithPlayers($championshipId),
        ];
    }

    public function updateTeamName(string $championshipId, string $ownerId, string $teamId, string $name): bool
    {
        $stmt = $this->pdo->prepare("
            update teams t
            set name = :name
            from championships c
            where t.championship_id = c.id
            and t.id = :team_id
            and t.championship_id = :championship_id
            and c.owner_id = :owner_id
            and c.status = 'in_progress'
        ");

        $stmt->execute([
            ':name' => $name,
            ':team_id' => $teamId,
            ':championship_id' => $championshipId,
            ':owner_id' => $ownerId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function addPlayerToTeam(string $championshipId, string $ownerId, string $teamId, string $name, string $role): bool
    {
        $this->pdo->beginTransaction();

        try {
            if (!$this->canMutateTeam($championshipId, $ownerId, $teamId)) {
                $this->pdo->rollBack();
                return false;
            }

            $playerStmt = $this->pdo->prepare("
                insert into players (
                    championship_id,
                    name
                ) values (
                    :championship_id,
                    :name
                )
                returning id
            ");

            $playerStmt->execute([
                ':championship_id' => $championshipId,
                ':name' => $name,
            ]);

            $playerId = (string) $playerStmt->fetchColumn();

            $memberStmt = $this->pdo->prepare("
                insert into team_members (
                    championship_id,
                    team_id,
                    player_id,
                    role
                ) values (
                    :championship_id,
                    :team_id,
                    :player_id,
                    :role
                )
            ");

            $memberStmt->execute([
                ':championship_id' => $championshipId,
                ':team_id' => $teamId,
                ':player_id' => $playerId,
                ':role' => $role,
            ]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updatePlayerRole(string $championshipId, string $ownerId, string $teamId, string $playerId, string $role): bool
    {
        $stmt = $this->pdo->prepare("
            update team_members tm
            set role = :role
            from championships c
            where tm.championship_id = c.id
            and tm.championship_id = :championship_id
            and tm.team_id = :team_id
            and tm.player_id = :player_id
            and c.owner_id = :owner_id
            and c.status = 'in_progress'
        ");

        $stmt->execute([
            ':role' => $role,
            ':championship_id' => $championshipId,
            ':team_id' => $teamId,
            ':player_id' => $playerId,
            ':owner_id' => $ownerId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function deletePlayer(string $championshipId, string $ownerId, string $teamId, string $playerId): bool
    {
        $stmt = $this->pdo->prepare("
            delete from players p
            using team_members tm, championships c
            where p.id = tm.player_id
            and p.championship_id = tm.championship_id
            and p.championship_id = c.id
            and p.id = :player_id
            and p.championship_id = :championship_id
            and tm.team_id = :team_id
            and c.owner_id = :owner_id
            and c.status = 'in_progress'
        ");

        $stmt->execute([
            ':player_id' => $playerId,
            ':championship_id' => $championshipId,
            ':team_id' => $teamId,
            ':owner_id' => $ownerId,
        ]);

        return $stmt->rowCount() > 0;
    }

    private function getGroupStandings(string $championshipId): array
    {
        $stmt = $this->pdo->prepare("
            select
                g.id as group_id,
                g.name as group_name,
                g.group_order,
                t.id as team_id,
                t.name as team_name,
                gt.position,
                coalesce(s.matches_played, 0) as matches_played,
                coalesce(s.wins, 0) as wins,
                coalesce(s.losses, 0) as losses,
                coalesce(s.sets_won, 0) as sets_won,
                coalesce(s.sets_lost, 0) as sets_lost,
                coalesce(s.sets_balance, 0) as sets_balance,
                coalesce(s.points_balance, 0) as points_balance
            from groups g
            join group_teams gt
                on gt.group_id = g.id
                and gt.championship_id = g.championship_id
            join teams t
                on t.id = gt.team_id
                and t.championship_id = gt.championship_id
            left join v_team_standings s
                on s.championship_id = gt.championship_id
                and s.group_id = gt.group_id
                and s.team_id = gt.team_id
            where g.championship_id = :championship_id
            order by g.group_order,
                coalesce(s.wins, 0) desc,
                coalesce(s.sets_balance, 0) desc,
                coalesce(s.points_balance, 0) desc,
                coalesce(gt.position, 999),
                t.name
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        return $this->mapStandingsByGroup($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function getTeamsWithPlayers(string $championshipId): array
    {
        $stmt = $this->pdo->prepare("
            select
                t.id as team_id,
                t.name as team_name,
                t.created_at as team_created_at,
                p.id as player_id,
                p.name as player_name,
                tm.role
            from teams t
            left join team_members tm
                on tm.team_id = t.id
                and tm.championship_id = t.championship_id
            left join players p
                on p.id = tm.player_id
                and p.championship_id = tm.championship_id
            where t.championship_id = :championship_id
            order by t.created_at, t.name, tm.role desc, p.created_at, p.name
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        $teams = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $teamId = (string) $row['team_id'];

            $teams[$teamId] ??= [
                'id' => $teamId,
                'name' => (string) $row['team_name'],
                'players' => [],
            ];

            if (!empty($row['player_id'])) {
                $teams[$teamId]['players'][] = [
                    'id' => (string) $row['player_id'],
                    'name' => (string) $row['player_name'],
                    'role' => (string) $row['role'],
                    'role_label' => $row['role'] === 'starter' ? 'Titular' : 'Reserva',
                ];
            }
        }

        return array_values($teams);
    }

    private function canMutateTeam(string $championshipId, string $ownerId, string $teamId): bool
    {
        $stmt = $this->pdo->prepare("
            select exists (
                select 1
                from teams t
                join championships c on c.id = t.championship_id
                where t.id = :team_id
                and t.championship_id = :championship_id
                and c.owner_id = :owner_id
                and c.status = 'in_progress'
            )
        ");

        $stmt->execute([
            ':team_id' => $teamId,
            ':championship_id' => $championshipId,
            ':owner_id' => $ownerId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private function getRoundRobinStandings(string $championshipId): array
    {
        $stmt = $this->pdo->prepare("
            select
                p.id as phase_id,
                t.id as team_id,
                t.name as team_name,
                coalesce(s.matches_played, 0) as matches_played,
                coalesce(s.wins, 0) as wins,
                coalesce(s.losses, 0) as losses,
                coalesce(s.sets_won, 0) as sets_won,
                coalesce(s.sets_lost, 0) as sets_lost,
                coalesce(s.sets_balance, 0) as sets_balance,
                coalesce(s.points_balance, 0) as points_balance
            from phases p
            join teams t on t.championship_id = p.championship_id
            left join v_team_standings s
                on s.championship_id = p.championship_id
                and s.phase_id = p.id
                and s.team_id = t.id
                and s.group_id is null
            where p.championship_id = :championship_id
            and p.type = 'round_robin'
            order by coalesce(s.wins, 0) desc,
                coalesce(s.sets_balance, 0) desc,
                coalesce(s.points_balance, 0) desc,
                t.name
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn (array $row, int $index): array => $this->normalizeStandingRow($row, $index + 1),
            $rows,
            array_keys($rows)
        );
    }

    private function getKnockoutRounds(string $championshipId): array
    {
        $stmt = $this->pdo->prepare("
            select
                m.id,
                m.round_number,
                m.match_order,
                m.status,
                m.team_a_id,
                m.team_b_id,
                m.winner_team_id,
                ta.name as team_a_name,
                tb.name as team_b_name,
                w.name as winner_name
            from matches m
            join phases p
                on p.id = m.phase_id
                and p.championship_id = m.championship_id
            join teams ta
                on ta.id = m.team_a_id
                and ta.championship_id = m.championship_id
            join teams tb
                on tb.id = m.team_b_id
                and tb.championship_id = m.championship_id
            left join teams w on w.id = m.winner_team_id
            where m.championship_id = :championship_id
            and p.type = 'knockout'
            order by m.round_number, m.match_order
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalRounds = 0;

        foreach ($rows as $match) {
            $totalRounds = max($totalRounds, (int) $match['round_number']);
        }

        $totalRounds = max($totalRounds, $this->getExpectedKnockoutRoundCount($championshipId));

        $rounds = [];

        foreach ($rows as $match) {
            $roundNumber = (int) $match['round_number'];
            $rounds[$roundNumber] ??= [
                'number' => $roundNumber,
                'label' => $this->formatRoundLabel($roundNumber, $totalRounds),
                'matches' => [],
            ];

            $rounds[$roundNumber]['matches'][] = $this->normalizeMatchRow($match);
        }

        return array_values($rounds);
    }

    private function getRecentMatches(string $championshipId, ?int $limit = 5): array
    {
        $limitSql = $limit === null ? '' : 'limit :limit';
        $stmt = $this->pdo->prepare("
            select
                m.id,
                m.round_number,
                m.match_order,
                m.status,
                m.played_at,
                to_char(m.played_at, 'DD/MM/YYYY') as played_date,
                p.type as phase_type,
                p.name as phase_name,
                g.name as group_name,
                ta.name as team_a_name,
                tb.name as team_b_name,
                w.name as winner_name
            from matches m
            join phases p
                on p.id = m.phase_id
                and p.championship_id = m.championship_id
            join teams ta
                on ta.id = m.team_a_id
                and ta.championship_id = m.championship_id
            join teams tb
                on tb.id = m.team_b_id
                and tb.championship_id = m.championship_id
            left join groups g
                on g.id = m.group_id
                and g.championship_id = m.championship_id
            left join teams w on w.id = m.winner_team_id
            where m.championship_id = :championship_id
            order by
                case when m.status = 'completed' then 0 else 1 end,
                m.played_at desc nulls last,
                p.phase_order,
                m.round_number,
                m.match_order
            {$limitSql}
        ");

        $stmt->bindValue(':championship_id', $championshipId);

        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();

        return array_map(
            fn (array $row): array => $this->normalizeRecentMatchRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    private function hasPhaseType(string $championshipId, string $type): bool
    {
        $stmt = $this->pdo->prepare("
            select exists (
                select 1
                from phases
                where championship_id = :championship_id
                and type = :type
            )
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':type' => $type,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    private function getExpectedKnockoutRoundCount(string $championshipId): int
    {
        $stmt = $this->pdo->prepare("
            select format
            from championships
            where id = :championship_id
            limit 1
        ");

        $stmt->execute([':championship_id' => $championshipId]);
        $format = (string) ($stmt->fetchColumn() ?: '');

        $entrantCount = match ($format) {
            'knockout' => $this->countChampionshipTeams($championshipId),
            'groups_and_knockout' => $this->countGroupStageQualifiers($championshipId),
            default => 0,
        };

        if ($entrantCount <= 1) {
            return 0;
        }

        $bracketSize = $this->nextPowerOfTwo($entrantCount);
        $roundCount = 0;

        while ($bracketSize > 1) {
            $roundCount++;
            $bracketSize = (int) ($bracketSize / 2);
        }

        return $roundCount;
    }

    private function countChampionshipTeams(string $championshipId): int
    {
        $stmt = $this->pdo->prepare("
            select count(*)::integer
            from teams
            where championship_id = :championship_id
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        return (int) $stmt->fetchColumn();
    }

    private function countGroupStageQualifiers(string $championshipId): int
    {
        $stmt = $this->pdo->prepare("
            select count(*)::integer
            from (
                select row_number() over (
                    partition by g.id
                    order by coalesce(gt.position, 999), t.name, t.id
                ) as group_rank
                from groups g
                join group_teams gt
                    on gt.group_id = g.id
                    and gt.championship_id = g.championship_id
                join teams t
                    on t.id = gt.team_id
                    and t.championship_id = gt.championship_id
                where g.championship_id = :championship_id
            ) ranked
            where group_rank <= 2
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        return (int) $stmt->fetchColumn();
    }

    private function nextPowerOfTwo(int $number): int
    {
        $power = 1;

        while ($power < $number) {
            $power *= 2;
        }

        return $power;
    }

    private function mapStandingsByGroup(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $groupId = (string) $row['group_id'];

            $groups[$groupId] ??= [
                'id' => $groupId,
                'name' => (string) $row['group_name'],
                'order' => (int) $row['group_order'],
                'teams' => [],
            ];

            $groups[$groupId]['teams'][] = $this->normalizeStandingRow(
                $row,
                count($groups[$groupId]['teams']) + 1
            );
        }

        return array_values($groups);
    }

    private function normalizeStandingRow(array $row, int $position): array
    {
        $wins = (int) ($row['wins'] ?? 0);

        return [
            'position' => $position,
            'team_id' => (string) ($row['team_id'] ?? ''),
            'team_name' => (string) ($row['team_name'] ?? 'Time'),
            'matches_played' => (int) ($row['matches_played'] ?? 0),
            'wins' => $wins,
            'losses' => (int) ($row['losses'] ?? 0),
            'sets' => (int) ($row['sets_won'] ?? 0) . ':' . (int) ($row['sets_lost'] ?? 0),
            'points' => $wins * 3,
        ];
    }

    private function normalizeMatchRow(array $row): array
    {
        return [
            'id' => (string) ($row['id'] ?? ''),
            'round_number' => (int) ($row['round_number'] ?? 1),
            'match_order' => (int) ($row['match_order'] ?? 1),
            'status' => (string) ($row['status'] ?? 'scheduled'),
            'team_a_id' => (string) ($row['team_a_id'] ?? ''),
            'team_a_name' => (string) ($row['team_a_name'] ?? 'A definir'),
            'team_b_id' => (string) ($row['team_b_id'] ?? ''),
            'team_b_name' => (string) ($row['team_b_name'] ?? 'A definir'),
            'winner_team_id' => (string) ($row['winner_team_id'] ?? ''),
            'winner_name' => (string) ($row['winner_name'] ?? ''),
        ];
    }

    private function normalizeRecentMatchRow(array $row): array
    {
        $match = $this->normalizeMatchRow($row);
        $phaseLabel = $this->formatPhaseLabel((string) ($row['phase_type'] ?? ''), (string) ($row['group_name'] ?? ''));

        return $match + [
            'phase_label' => $phaseLabel,
            'played_date' => (string) ($row['played_date'] ?? ''),
            'score' => $match['status'] === 'completed' ? 'Finalizada' : 'x',
        ];
    }

    private function formatPhaseLabel(string $phaseType, string $groupName): string
    {
        if ($phaseType === 'group_stage' && $groupName !== '') {
            return 'Grupo ' . $groupName;
        }

        return match ($phaseType) {
            'knockout' => 'Mata-mata',
            'round_robin' => 'Pontos corridos',
            default => 'Partida',
        };
    }

    private function formatRoundLabel(int $roundNumber, ?int $totalRounds = null): string
    {
        if ($totalRounds !== null && $totalRounds > 0) {
            $remainingRounds = $totalRounds - $roundNumber + 1;

            return match ($remainingRounds) {
                1 => 'Final',
                2 => 'Semifinais',
                3 => 'Quartas',
                4 => 'Oitavas',
                default => 'Rodada ' . $roundNumber,
            };
        }

        return match ($roundNumber) {
            1 => 'Rodada 1',
            2 => 'Semifinais',
            3 => 'Final',
            default => 'Rodada ' . $roundNumber,
        };
    }
}
