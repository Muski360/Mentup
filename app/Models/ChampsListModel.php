<?php

class ChampsListModel
{
    private const ALLOWED_STATUSES = ['in_progress', 'finished'];

    public function __construct(private PDO $pdo)
    {
    }

    public function getChampionshipsByStatus(string $userId, string $status): array
    {
        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException('Invalid championship status.');
        }

        $stmt = $this->pdo->prepare("
            select
                c.id,
                c.name,
                c.description,
                c.photo_path,
                c.team_mode,
                c.format,
                c.best_of,
                c.status,
                c.created_at,
                c.finished_at,
                to_char(c.created_at, 'DD/MM/YYYY') as start_date,
                'Volei de praia' as modality,
                (
                    select count(*)
                    from teams t
                    where t.championship_id = c.id
                ) as total_teams,
                (
                    select count(*)
                    from matches m
                    where m.championship_id = c.id
                    and m.status = 'completed'
                ) as completed_matches
            from championships c
            where c.owner_id = :owner_id
            and c.status = :status
            order by c.created_at desc
        ");

        $stmt->execute([
            ':owner_id' => $userId,
            ':status' => $status,
        ]);

        return array_map(
            fn (array $championship): array => $this->normalizeChampionship($championship),
            $stmt->fetchAll()
        );
    }

    public function getChampionshipLists(string $userId): array
    {
        return [
            'in_progress' => $this->getChampionshipsByStatus($userId, 'in_progress'),
            'finished' => $this->getChampionshipsByStatus($userId, 'finished'),
        ];
    }

    private function normalizeChampionship(array $championship): array
    {
        return [
            'id' => (string) ($championship['id'] ?? ''),
            'name' => (string) ($championship['name'] ?? 'Campeonato sem nome'),
            'description' => (string) ($championship['description'] ?? ''),
            'photo_path' => (string) ($championship['photo_path'] ?? ''),
            'team_mode' => $this->formatTeamMode((string) ($championship['team_mode'] ?? '')),
            'format' => (string) ($championship['format'] ?? ''),
            'best_of' => (string) ($championship['best_of'] ?? ''),
            'status' => (string) ($championship['status'] ?? ''),
            'status_label' => $this->formatStatus((string) ($championship['status'] ?? '')),
            'modality' => (string) ($championship['modality'] ?? 'Vôlei de praia'),
            'total_teams' => (int) ($championship['total_teams'] ?? 0),
            'completed_matches' => (int) ($championship['completed_matches'] ?? 0),
            'start_date' => (string) ($championship['start_date'] ?? ''),
        ];
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'finished' => 'Finalizado',
            'in_progress' => 'Em andamento',
            default => 'Indefinido',
        };
    }

private function formatTeamMode(string $teamMode): string
{
    return match ($teamMode) {
        'duo' => 'Duplas',
        'quartet' => 'Quartetos',
        default => 'Indefinido',
    };
}
}
