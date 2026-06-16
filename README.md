# Mentup

Mentup é uma aplicação web em PHP para organizar campeonatos de vôlei de praia. O sistema permite criar conta, entrar na plataforma, cadastrar campeonatos, montar times, registrar jogadores, gerar partidas, lançar resultados, acompanhar classificação e encerrar competições.

A aplicação usa PHP 8.3, PostgreSQL, HTML, CSS e JavaScript. O banco pode ser executado em uma instância PostgreSQL comum ou em Supabase usado apenas como infraestrutura de banco.

## Funcionalidades

- Cadastro, login e logout de usuários.
- Dashboard com indicadores do usuário logado.
- Listagem de campeonatos por status.
- Criação de campeonato de vôlei de praia.
- Cadastro inicial de times e jogadores.
- Geração automática de fases, grupos, partidas e chaveamento.
- Visualização de detalhes do campeonato.
- Edição de dados do campeonato, times, jogadores e funções.
- Lançamento e edição de resultados por sets.
- Avanço automático do mata-mata.
- Encerramento de campeonato.
- Exclusão de conta.

## Stack

- PHP `^8.3`
- Composer
- `vlucas/phpdotenv`
- PostgreSQL com PDO (`pdo_pgsql`)
- Apache no Docker
- HTML, CSS e JavaScript
- Lucide Icons via CDN

## Como Rodar

Instale as dependências:

```bash
composer install
```

Crie um arquivo `.env` na raiz do projeto:

```env
DB_HOST=
DB_PORT=5432
DB_NAME=postgres
DB_USER=
DB_PASSWORD=
DB_SSLMODE=require
```

Para rodar localmente com o servidor embutido do PHP:

```bash
php -S 127.0.0.1:8000 -t public
```

Para rodar com Docker:

```bash
docker compose up --build
```

Com Docker, a aplicação fica disponível em:

```text
http://localhost:8080
```

## Estrutura do Projeto

```text
Mentup/
|-- app/
|   |-- Controllers/
|   |-- Models/
|   |-- Services/
|   |-- Views/
|       |-- fragments/
|       |-- layout/
|-- config/
|   |-- database.php
|   |-- supabase.php
|-- docker/
|-- public/
|   |-- assets/
|   |   |-- css/
|   |   |-- img/
|   |   |-- js/
|   |-- index.php
|   |-- login.php
|   |-- register.php
|   |-- dashboard.php
|   |-- championship-list.php
|   |-- create-championship.php
|   |-- championship.php
|   |-- championship-results.php
|   |-- settings.php
|-- composer.json
|-- docker-compose.yml
|-- Dockerfile
|-- README.md
```

## Banco de Dados

O banco é relacional e gira em torno de usuários, campeonatos, times, jogadores, fases, partidas e sets.

Cada usuário possui seus próprios campeonatos. Cada campeonato possui seus times, jogadores, fases e partidas. Os resultados são salvos por set, e o banco atualiza o vencedor da partida a partir desses sets.

## Tabelas

### `users`

Guarda as contas da aplicação.

| Campo | Tipo | Função |
| --- | --- | --- |
| `id` | `uuid` | Identificador do usuário. |
| `name` | `text` | Nome do usuário. |
| `email` | `citext` | E-mail usado no login. |
| `password_hash` | `text` | Senha criptografada com `password_hash()`. |
| `avatar_path` | `text` | Caminho opcional da imagem de perfil. |
| `is_active` | `bool` | Define se a conta pode acessar o sistema. |
| `created_at` | `timestamptz` | Data de criação. |
| `updated_at` | `timestamptz` | Data da última atualização. |

### `championships`

Guarda os campeonatos criados pelos usuários.

| Campo | Tipo | Função |
| --- | --- | --- |
| `id` | `uuid` | Identificador do campeonato. |
| `owner_id` | `uuid` | Usuário dono do campeonato. |
| `name` | `text` | Nome do campeonato. |
| `description` | `text` | Descrição opcional. |
| `photo_path` | `text` | Caminho opcional da foto do campeonato. |
| `team_mode` | `team_mode` | Quantidade de titulares por time. |
| `format` | `tournament_format` | Formato do campeonato. |
| `best_of` | `match_best_of` | Critério de vitória da partida. |
| `status` | `championship_status` | Situação do campeonato. |
| `created_at` | `timestamptz` | Data de criação. |
| `updated_at` | `timestamptz` | Data da última atualização. |
| `finished_at` | `timestamptz` | Data de encerramento. |

### `teams`

Guarda os times de um campeonato.

| Campo | Tipo | Função |
| --- | --- | --- |
| `id` | `uuid` | Identificador do time. |
| `championship_id` | `uuid` | Campeonato ao qual o time pertence. |
| `name` | `text` | Nome do time. |
| `seed` | `int4` | Ordem opcional para montagem inicial. |
| `created_at` | `timestamptz` | Data de criação. |
| `updated_at` | `timestamptz` | Data da última atualização. |

### `players`

Guarda jogadores cadastrados dentro de um campeonato.

| Campo | Tipo | Função |
| --- | --- | --- |
| `id` | `uuid` | Identificador do jogador. |
| `championship_id` | `uuid` | Campeonato ao qual o jogador pertence. |
| `name` | `text` | Nome do jogador. |
| `notes` | `text` | Observações opcionais. |
| `created_at` | `timestamptz` | Data de criação. |

### `team_members`

Liga jogadores aos times.

| Campo | Tipo | Função |
| --- | --- | --- |
| `championship_id` | `uuid` | Campeonato da relação. |
| `team_id` | `uuid` | Time do jogador. |
| `player_id` | `uuid` | Jogador vinculado. |
| `role` | `team_member_role` | Função do jogador: titular ou reserva. |
| `created_at` | `timestamptz` | Data de criação. |

### `phases`

Guarda as fases do campeonato.

| Campo | Tipo | Função |
| --- | --- | --- |
| `id` | `uuid` | Identificador da fase. |
| `championship_id` | `uuid` | Campeonato da fase. |
| `type` | `phase_type` | Tipo da fase. |
| `name` | `text` | Nome exibido da fase. |
| `phase_order` | `int4` | Ordem da fase no campeonato. |
| `created_at` | `timestamptz` | Data de criação. |
| `updated_at` | `timestamptz` | Data da última atualização. |

### `groups`

Guarda os grupos da fase de grupos.

| Campo | Tipo | Função |
| --- | --- | --- |
| `id` | `uuid` | Identificador do grupo. |
| `championship_id` | `uuid` | Campeonato do grupo. |
| `phase_id` | `uuid` | Fase à qual o grupo pertence. |
| `name` | `text` | Nome do grupo, como `A`, `B` ou `C`. |
| `group_order` | `int4` | Ordem do grupo. |
| `created_at` | `timestamptz` | Data de criação. |
| `updated_at` | `timestamptz` | Data da última atualização. |

### `group_teams`

Liga times aos grupos.

| Campo | Tipo | Função |
| --- | --- | --- |
| `championship_id` | `uuid` | Campeonato da relação. |
| `group_id` | `uuid` | Grupo do time. |
| `team_id` | `uuid` | Time vinculado ao grupo. |
| `position` | `int4` | Posição inicial no grupo. |
| `created_at` | `timestamptz` | Data de criação. |

### `matches`

Guarda as partidas geradas pela aplicação.

| Campo | Tipo | Função |
| --- | --- | --- |
| `id` | `uuid` | Identificador da partida. |
| `championship_id` | `uuid` | Campeonato da partida. |
| `phase_id` | `uuid` | Fase da partida. |
| `group_id` | `uuid` | Grupo da partida, quando existir. |
| `team_a_id` | `uuid` | Primeiro time. |
| `team_b_id` | `uuid` | Segundo time. |
| `winner_team_id` | `uuid` | Time vencedor. |
| `status` | `match_status` | Situação da partida. |
| `round_number` | `int4` | Rodada. |
| `match_order` | `int4` | Ordem da partida na rodada. |
| `scheduled_at` | `timestamptz` | Data agendada opcional. |
| `played_at` | `timestamptz` | Data em que o resultado foi lançado. |
| `notes` | `text` | Observações da partida. |
| `created_at` | `timestamptz` | Data de criação. |
| `updated_at` | `timestamptz` | Data da última atualização. |

### `match_sets`

Guarda o placar de cada set.

| Campo | Tipo | Função |
| --- | --- | --- |
| `id` | `uuid` | Identificador do set. |
| `championship_id` | `uuid` | Campeonato do set. |
| `match_id` | `uuid` | Partida do set. |
| `set_number` | `int4` | Número do set, de 1 a 3. |
| `team_a_points` | `int4` | Pontos do primeiro time. |
| `team_b_points` | `int4` | Pontos do segundo time. |
| `created_at` | `timestamptz` | Data de criação. |
| `updated_at` | `timestamptz` | Data da última atualização. |

## Enums

| Enum | Valores |
| --- | --- |
| `championship_status` | `in_progress`, `finished` |
| `tournament_format` | `groups_and_knockout`, `knockout`, `round_robin` |
| `team_mode` | `duo`, `quartet` |
| `team_member_role` | `starter`, `reserve` |
| `phase_type` | `group_stage`, `knockout`, `round_robin` |
| `match_best_of` | `best_of_1`, `best_of_3` |
| `match_status` | `scheduled`, `completed` |

## Relacionamentos

```text
users
  -> championships
      -> teams
          -> team_members
              -> players
      -> phases
          -> groups
              -> group_teams
          -> matches
              -> match_sets
```

Regras principais:

- Um usuário pode ter vários campeonatos.
- Um campeonato pertence a apenas um usuário.
- Times, jogadores, fases, grupos, partidas e sets sempre pertencem a um campeonato.
- Um jogador só pode participar de um time dentro do mesmo campeonato.
- Em campeonato de dupla, cada time pode ter até 2 titulares.
- Em campeonato de quarteto, cada time pode ter até 4 titulares.
- Reservas são permitidos.
- Uma partida sempre possui dois times diferentes.
- O vencedor precisa ser um dos dois times da partida.
- Partidas de fase de grupos precisam ter `group_id`.
- Partidas de mata-mata e pontos corridos não usam `group_id`.

## Fluxo do Sistema

### 1. Cadastro

O cadastro acontece em `register.php`.

Fluxo:

1. O usuário informa nome, e-mail, senha e confirmação de senha.
2. A aplicação valida os dados.
3. A aplicação verifica se o e-mail já existe.
4. A senha é criptografada com `password_hash()`.
5. O usuário é salvo na tabela `users`.
6. A sessão PHP é iniciada com `user_id`, `user_name` e `user_email`.
7. O usuário é redirecionado para o dashboard.

### 2. Login

O login acontece em `login.php`.

Fluxo:

1. A aplicação busca o usuário pelo e-mail.
2. A conta precisa estar ativa (`is_active = true`).
3. A senha é validada com `password_verify()`.
4. O ID da sessão é regenerado.
5. Os dados principais do usuário são salvos na sessão.
6. O usuário acessa o dashboard.

### 3. Criação de Campeonato

O campeonato é criado em `create-championship.php`.

O formulário recebe:

- nome do campeonato;
- descrição opcional;
- modalidade fixa de vôlei de praia;
- quantidade de times;
- formato do campeonato;
- critério de vitória;
- jogadores por time;
- nomes dos times;
- jogadores de cada time.

Ao salvar, a aplicação cria tudo em uma transação:

1. Cria o registro em `championships`.
2. Cria os times em `teams`.
3. Cria jogadores em `players`.
4. Liga jogadores aos times em `team_members`.
5. Gera fases e partidas automaticamente.

### 4. Geração de Fases e Partidas

A estrutura do campeonato é gerada pelo serviço `ChampionshipScheduleGenerator`.

Formatos disponíveis:

| Formato | Funcionamento |
| --- | --- |
| `groups_and_knockout` | Cria fase de grupos, distribui os times, gera partidas todos contra todos por grupo e prepara uma fase mata-mata. |
| `knockout` | Cria mata-mata direto e gera a primeira rodada. |
| `round_robin` | Cria pontos corridos e gera partidas todos contra todos. |

Na fase de grupos, a quantidade de grupos é calculada com base no total de times. Cada grupo recebe partidas internas em formato todos contra todos.

### 5. Detalhes do Campeonato

A tela `championship.php` mostra:

- resumo do campeonato;
- total de equipes;
- classificação;
- chaveamento;
- partidas;
- informações gerais;
- times e jogadores.

Também existe um editor para:

- renomear time;
- adicionar jogador;
- alterar função do jogador;
- remover jogador.

### 6. Lançamento de Resultados

Os resultados são lançados em `championship-results.php`.

Fluxo:

1. A aplicação lista partidas pendentes e partidas concluídas.
2. O usuário escolhe uma partida.
3. Informa os pontos de cada set.
4. A aplicação valida as regras do vôlei.
5. Os sets são salvos em `match_sets`.
6. O banco atualiza a partida em `matches`.
7. O sistema tenta avançar o chaveamento.

Regras de pontuação:

- Set 1 e set 2 precisam de pelo menos 21 pontos para o vencedor.
- Set 3 precisa de pelo menos 15 pontos para o vencedor.
- Todo set precisa ter diferença mínima de 2 pontos.
- Nenhum set pode terminar empatado.
- Em `best_of_1`, existe apenas um set.
- Em `best_of_3`, o terceiro set só é necessário se cada time vencer um dos dois primeiros sets.

### 7. Classificação

A classificação é calculada por uma view chamada `v_team_standings`.

Critérios principais:

1. vitórias;
2. saldo de sets;
3. saldo de pontos;
4. posição inicial ou nome do time como desempate final.

### 8. Mata-Mata

O mata-mata é sincronizado pelo serviço `KnockoutBracketService`.

Em campeonatos com grupos:

1. O sistema espera todos os jogos dos grupos terminarem.
2. Classifica até 2 times por grupo.
3. Cria a primeira rodada do mata-mata.
4. Quando uma rodada termina, cria a próxima.
5. O processo continua até a final.

Em mata-mata direto:

1. A primeira rodada é criada na geração inicial.
2. As próximas rodadas são criadas conforme os vencedores avançam.

Quando um resultado já alimentou uma fase posterior, ele fica bloqueado para edição.

### 9. Encerramento

Ao encerrar um campeonato:

1. `championships.status` muda para `finished`.
2. `finished_at` recebe a data de encerramento.
3. O campeonato passa a ficar congelado para edições.
4. Resultados e dados principais deixam de poder ser alterados.

## Regras do Banco

O banco deve manter regras para:

- atualizar `updated_at` automaticamente;
- preencher `finished_at` quando um campeonato é encerrado;
- impedir edição de campeonato finalizado;
- validar limite de titulares por time;
- garantir que fases combinam com o formato do campeonato;
- garantir que partidas de grupo usam times do grupo correto;
- impedir partidas com times iguais;
- impedir vencedor fora da partida;
- validar pontuação dos sets;
- sincronizar vencedor da partida a partir dos sets.

## Observações Técnicas

- A conexão com o banco é feita por `config/database.php`.
- A aplicação usa sessão PHP para autenticação.
- A modalidade está fixa como vôlei de praia.
- O upload de foto de campeonato aparece como estrutura prevista no banco, mas não está implementado na interface.
- `config/supabase.php` existe, mas não possui lógica no estado atual do projeto.

## Autor

Desenvolvido por Murilo Dovigo Bastos como projeto final para o SENAI.

Professor orientador: Jorge Carneiro.

## Licença

Projeto com finalidade acadêmica e educacional.
