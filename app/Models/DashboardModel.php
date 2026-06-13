<?php

class DashboardModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getStatsByUser(string $userId): array
    {
        $stmt = $this->pdo->prepare("
            select
                (
                    select count(*)
                    from championships c
                    where c.owner_id = :owner_id_championships
                ) as total_championships,

                (
                    select count(*)
                    from championships c
                    where c.owner_id = :owner_id_championships_month
                    and c.created_at >= now() - interval '1 month'
                ) as total_championships_month,

                (
                    select count(*)
                    from teams t
                    inner join championships c on c.id = t.championship_id
                    where c.owner_id = :owner_id_teams
                ) as total_teams,

                (
                    select count(*)
                    from teams t
                    inner join championships c on c.id = t.championship_id
                    where c.owner_id = :owner_id_teams_month
                    and t.created_at >= now() - interval '1 month'
                ) as total_teams_month,

                (
                    select count(*)
                    from matches m
                    inner join championships c on c.id = m.championship_id
                    where c.owner_id = :owner_id_matches
                ) as total_matches,

                (
                    select count(*)
                    from matches m
                    inner join championships c on c.id = m.championship_id
                    where c.owner_id = :owner_id_matches_month
                    and m.created_at >= now() - interval '1 month'
                ) as total_matches_month
        ");

        $stmt->execute([
            ':owner_id_championships' => $userId,
            ':owner_id_championships_month' => $userId,
            ':owner_id_teams' => $userId,
            ':owner_id_teams_month' => $userId,
            ':owner_id_matches' => $userId,
            ':owner_id_matches_month' => $userId,
        ]);

        $stats = $stmt->fetch() ?: [];

        return [
            'total_championships' => (int) ($stats['total_championships'] ?? 0),
            'total_championships_month' => (int) ($stats['total_championships_month'] ?? 0),
            'total_teams' => (int) ($stats['total_teams'] ?? 0),
            'total_teams_month' => (int) ($stats['total_teams_month'] ?? 0),
            'total_matches' => (int) ($stats['total_matches'] ?? 0),
            'total_matches_month' => (int) ($stats['total_matches_month'] ?? 0),
        ];
    }
}
