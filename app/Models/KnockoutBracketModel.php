<?php

class KnockoutBracketModel
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

    public function getContext(string $championshipId, string $ownerId): ?array
    {
        $stmt = $this->pdo->prepare("
            select
                c.id,
                c.owner_id,
                c.format,
                c.status,
                p.id as knockout_phase_id
            from championships c
            left join phases p
                on p.championship_id = c.id
                and p.type = 'knockout'
            where c.id = :championship_id
            and c.owner_id = :owner_id
            limit 1
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':owner_id' => $ownerId,
        ]);

        $context = $stmt->fetch(PDO::FETCH_ASSOC);

        return $context ?: null;
    }

    public function getOrderedTeams(string $championshipId): array
    {
        $stmt = $this->pdo->prepare("
            select id, name
            from teams
            where championship_id = :championship_id
            order by coalesce(seed, 2147483647), created_at, name, id
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        return array_map(
            fn (array $team): array => [
                'id' => (string) $team['id'],
                'name' => (string) $team['name'],
            ],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function getGroupStageCompletion(string $championshipId): array
    {
        $stmt = $this->pdo->prepare("
            select
                count(*)::integer as total_matches,
                count(*) filter (where m.status = 'completed')::integer as completed_matches
            from matches m
            join phases p
                on p.id = m.phase_id
                and p.championship_id = m.championship_id
            where m.championship_id = :championship_id
            and p.type = 'group_stage'
        ");

        $stmt->execute([':championship_id' => $championshipId]);
        $completion = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $total = (int) ($completion['total_matches'] ?? 0);
        $completed = (int) ($completion['completed_matches'] ?? 0);

        return [
            'total_matches' => $total,
            'completed_matches' => $completed,
            'is_complete' => $total > 0 && $total === $completed,
        ];
    }

    public function getQualifiedGroupTeams(string $championshipId, int $limitPerGroup = 2): array
    {
        $stmt = $this->pdo->prepare("
            select team_id, team_name
            from (
                select
                    g.group_order,
                    t.id as team_id,
                    t.name as team_name,
                    row_number() over (
                        partition by g.id
                        order by
                            coalesce(s.wins, 0) desc,
                            coalesce(s.sets_balance, 0) desc,
                            coalesce(s.points_balance, 0) desc,
                            coalesce(gt.position, 999),
                            t.name,
                            t.id
                    ) as group_rank
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
            ) ranked
            where group_rank <= :limit_per_group
            order by group_rank, group_order, team_name, team_id
        ");

        $stmt->bindValue(':championship_id', $championshipId);
        $stmt->bindValue(':limit_per_group', $limitPerGroup, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn (array $team): array => [
                'id' => (string) $team['team_id'],
                'name' => (string) $team['team_name'],
            ],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function hasKnockoutMatches(string $championshipId): bool
    {
        $stmt = $this->pdo->prepare("
            select exists (
                select 1
                from matches m
                join phases p
                    on p.id = m.phase_id
                    and p.championship_id = m.championship_id
                where m.championship_id = :championship_id
                and p.type = 'knockout'
            )
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        return (bool) $stmt->fetchColumn();
    }

    public function hasKnockoutRound(string $championshipId, int $roundNumber): bool
    {
        $stmt = $this->pdo->prepare("
            select exists (
                select 1
                from matches m
                join phases p
                    on p.id = m.phase_id
                    and p.championship_id = m.championship_id
                where m.championship_id = :championship_id
                and p.type = 'knockout'
                and m.round_number = :round_number
            )
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':round_number' => $roundNumber,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function getKnockoutRounds(string $championshipId): array
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
            left join teams w
                on w.id = m.winner_team_id
                and w.championship_id = m.championship_id
            where m.championship_id = :championship_id
            and p.type = 'knockout'
            order by m.round_number, m.match_order
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        $rounds = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $match) {
            $roundNumber = (int) $match['round_number'];

            $rounds[$roundNumber] ??= [
                'number' => $roundNumber,
                'matches' => [],
            ];

            $rounds[$roundNumber]['matches'][] = [
                'id' => (string) $match['id'],
                'round_number' => $roundNumber,
                'match_order' => (int) $match['match_order'],
                'status' => (string) $match['status'],
                'team_a_id' => (string) $match['team_a_id'],
                'team_a_name' => (string) $match['team_a_name'],
                'team_b_id' => (string) $match['team_b_id'],
                'team_b_name' => (string) $match['team_b_name'],
                'winner_team_id' => (string) ($match['winner_team_id'] ?? ''),
                'winner_name' => (string) ($match['winner_name'] ?? ''),
            ];
        }

        return array_values($rounds);
    }

    public function createKnockoutMatch(
        string $championshipId,
        string $phaseId,
        string $teamAId,
        string $teamBId,
        int $roundNumber,
        int $matchOrder,
        string $notes
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
                null,
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
            ':team_a_id' => $teamAId,
            ':team_b_id' => $teamBId,
            ':round_number' => $roundNumber,
            ':match_order' => $matchOrder,
            ':notes' => $notes,
        ]);
    }

    public function isMatchResultLocked(string $championshipId, string $matchId): bool
    {
        $stmt = $this->pdo->prepare("
            select
                case
                    when p.type = 'group_stage' then exists (
                        select 1
                        from matches km
                        join phases kp
                            on kp.id = km.phase_id
                            and kp.championship_id = km.championship_id
                        where km.championship_id = m.championship_id
                        and kp.type = 'knockout'
                    )
                    when p.type = 'knockout' then exists (
                        select 1
                        from matches nm
                        join phases np
                            on np.id = nm.phase_id
                            and np.championship_id = nm.championship_id
                        where nm.championship_id = m.championship_id
                        and np.type = 'knockout'
                        and nm.round_number > m.round_number
                    )
                    else false
                end as is_locked
            from matches m
            join phases p
                on p.id = m.phase_id
                and p.championship_id = m.championship_id
            where m.id = :match_id
            and m.championship_id = :championship_id
            limit 1
        ");

        $stmt->execute([
            ':match_id' => $matchId,
            ':championship_id' => $championshipId,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
