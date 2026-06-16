<?php

class ChampsCreateModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createChampionship(string $ownerId, array $data, ?callable $afterCreate = null): string
    {
        $this->pdo->beginTransaction();

        try {
            $championshipId = $this->insertChampionship($ownerId, $data);
            $this->insertTeams($championshipId, $data['teams']);

            // Mantem campeonato, times, fases e partidas na mesma transacao.
            if ($afterCreate !== null) {
                $afterCreate($championshipId);
            }

            $this->pdo->commit();

            return $championshipId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function insertChampionship(string $ownerId, array $data): string
    {
        $stmt = $this->pdo->prepare("
            insert into championships (
                owner_id,
                name,
                description,
                photo_path,
                team_mode,
                format,
                best_of
            ) values (
                :owner_id,
                :name,
                :description,
                :photo_path,
                :team_mode,
                :format,
                :best_of
            )
            returning id
        ");

        $stmt->execute([
            ':owner_id' => $ownerId,
            ':name' => $data['name'],
            ':description' => $data['description'] ?: null,
            ':photo_path' => null,
            ':team_mode' => $data['team_mode'],
            ':format' => $data['format'],
            ':best_of' => $data['best_of'],
        ]);

        return (string) $stmt->fetchColumn();
    }

private function insertTeams(string $championshipId, array $teams): void
{
    $teamStmt = $this->pdo->prepare("
        insert into teams (
            championship_id,
            name
        ) values (
            :championship_id,
            :name
        )
        returning id
    ");

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

    $teamMemberStmt = $this->pdo->prepare("
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

    foreach ($teams as $team) {
        $teamName = trim($team['name'] ?? '');

        if ($teamName === '') {
            continue;
        }

        $teamStmt->execute([
            ':championship_id' => $championshipId,
            ':name' => $teamName,
        ]);

        $teamId = (string) $teamStmt->fetchColumn();

        $players = $team['players'] ?? [];

        foreach ($players as $player) {
            $playerName = trim((string) ($player['name'] ?? ''));
            $playerRole = ($player['role'] ?? 'reserve') === 'starter' ? 'starter' : 'reserve';

            if ($playerName === '') {
                continue;
            }

            $playerStmt->execute([
                ':championship_id' => $championshipId,
                ':name' => $playerName,
            ]);

            $playerId = (string) $playerStmt->fetchColumn();

            $teamMemberStmt->execute([
                ':championship_id' => $championshipId,
                ':team_id' => $teamId,
                ':player_id' => $playerId,
                ':role' => $playerRole,
            ]);
        }
    }
}
}
