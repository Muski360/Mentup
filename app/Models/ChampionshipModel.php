<?php

class ChampionshipModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByOwner(string $championshipId, string $ownerId): ?array
    {
        $stmt = $this->pdo->prepare("
            select
                c.id,
                c.owner_id,
                c.name,
                c.description,
                c.photo_path,
                c.team_mode,
                c.format,
                c.best_of,
                c.status,
                c.created_at,
                c.finished_at,
                to_char(c.created_at, 'DD/MM/YYYY') as created_date,
                to_char(c.finished_at, 'DD/MM/YYYY') as finished_date,
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
            where c.id = :championship_id
            and c.owner_id = :owner_id
            limit 1
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':owner_id' => $ownerId,
        ]);

        $championship = $stmt->fetch();

        if (!$championship) {
            return null;
        }

        return $this->normalizeChampionship($championship);
    }

    public function finish(string $championshipId, string $ownerId): bool
    {
        $stmt = $this->pdo->prepare("
            update championships
            set status = 'finished'
            where id = :championship_id
            and owner_id = :owner_id
            and status = 'in_progress'
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':owner_id' => $ownerId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function updateDetails(string $championshipId, string $ownerId, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            update championships
            set
                name = :name,
                description = :description
            where id = :championship_id
            and owner_id = :owner_id
            and status = 'in_progress'
        ");

        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] !== '' ? $data['description'] : null,
            ':championship_id' => $championshipId,
            ':owner_id' => $ownerId,
        ]);

        return $stmt->rowCount() > 0;
    }

    private function normalizeChampionship(array $championship): array
    {
        return [
            'id' => (string) ($championship['id'] ?? ''),
            'name' => (string) ($championship['name'] ?? 'Campeonato sem nome'),
            'description' => (string) ($championship['description'] ?? ''),
            'photo_path' => (string) ($championship['photo_path'] ?? ''),
            'team_mode' => (string) ($championship['team_mode'] ?? ''),
            'team_mode_label' => $this->formatTeamMode((string) ($championship['team_mode'] ?? '')),
            'format' => (string) ($championship['format'] ?? ''),
            'format_label' => $this->formatTournamentFormat((string) ($championship['format'] ?? '')),
            'best_of' => (string) ($championship['best_of'] ?? ''),
            'status' => (string) ($championship['status'] ?? ''),
            'status_label' => $this->formatStatus((string) ($championship['status'] ?? '')),
            'modality' => 'Vôlei de Praia',
            'total_teams' => (int) ($championship['total_teams'] ?? 0),
            'completed_matches' => (int) ($championship['completed_matches'] ?? 0),
            'created_date' => (string) ($championship['created_date'] ?? ''),
            'finished_date' => (string) ($championship['finished_date'] ?? ''),
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

    private function formatTournamentFormat(string $format): string
    {
        return match ($format) {
            'groups_and_knockout' => 'Fase de grupos + Eliminação simples',
            'knockout' => 'Eliminação simples',
            'round_robin' => 'Pontos corridos',
            default => 'Indefinido',
        };
    }
}
