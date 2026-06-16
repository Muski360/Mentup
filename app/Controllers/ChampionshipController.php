<?php

require_once __DIR__ . '/../Models/ChampionshipModel.php';
require_once __DIR__ . '/../Services/ChampionshipScheduleGenerator.php';

class ChampionshipController
{
    public function show(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $championshipId = trim((string) ($_GET['id'] ?? ''));

        if ($championshipId === '') {
            header('Location: championship-list.php');
            exit;
        }

        require __DIR__ . '/../../config/database.php';

        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection was not initialized.');
        }

        $model = new ChampionshipModel($pdo);
        $structureModel = new ChampionshipStructureModel($pdo);
        $scheduleGenerator = new ChampionshipScheduleGenerator($structureModel);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($model, $structureModel, $championshipId, (string) $_SESSION['user_id']);
        }

        $championship = $model->findByOwner($championshipId, (string) $_SESSION['user_id']);

        if ($championship === null) {
            header('Location: championship-list.php');
            exit;
        }

        $generationSnack = null;

        if ($championship['status'] === 'in_progress') {
            try {
                $scheduleGenerator->ensureGenerated($championshipId, (string) $_SESSION['user_id']);
            } catch (Throwable $e) {
                $generationSnack = [
                    'type' => 'error',
                    'message' => 'Erro ao gerar fases e partidas.',
                ];
            }
        }

        $championshipStructure = $structureModel->getStructure($championshipId);

        $pageTitle = $championship['name'] . ' - Mentup';
        $basePath = '';
        $pageStyles = ['assets/css/dashboard.css', 'assets/css/championship-detail.css'];
        $bodyClass = 'dashboard-page championship-detail-page';
        $hideChrome = true;
        $activeMenu = 'championships';
        $dashboardUserName = $_SESSION['user_name'] ?? 'Organizador';
        $snack = $_SESSION['snack'] ?? $generationSnack;
        unset($_SESSION['snack']);

        require __DIR__ . '/../Views/championship.view.php';
    }

    private function handlePost(
        ChampionshipModel $model,
        ChampionshipStructureModel $structureModel,
        string $championshipId,
        string $ownerId
    ): void
    {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'finish') {
            $finished = $model->finish($championshipId, $ownerId);

            $_SESSION['snack'] = [
                'type' => $finished ? 'success' : 'error',
                'message' => $finished ? 'Campeonato encerrado com sucesso.' : 'Erro ao encerrar o campeonato.',
            ];
        }

        if ($action === 'update_details') {
            [$data, $errors] = $this->validateDetails($_POST);

            if (!empty($errors)) {
                $_SESSION['snack'] = [
                    'type' => 'error',
                    'message' => $errors[0],
                ];
            } else {
                try {
                    $updated = $model->updateDetails($championshipId, $ownerId, $data);

                    $_SESSION['snack'] = [
                        'type' => $updated ? 'success' : 'error',
                        'message' => $updated ? 'Campeonato atualizado com sucesso.' : 'Erro ao atualizar o campeonato.',
                    ];
                } catch (PDOException $e) {
                    $_SESSION['snack'] = [
                        'type' => 'error',
                        'message' => 'Erro ao atualizar campeonato.',
                    ];
                }
            }
        }

        if ($action === 'update_team_name') {
            [$data, $errors] = $this->validateTeamName($_POST);
            $this->runTeamMutation($errors, fn (): bool => $structureModel->updateTeamName(
                $championshipId,
                $ownerId,
                $data['team_id'],
                $data['name']
            ), 'Nome do time atualizado com sucesso.', 'Erro ao atualizar nome do time.');
        }

        if ($action === 'add_team_player') {
            [$data, $errors] = $this->validateNewPlayer($_POST);
            $this->runTeamMutation($errors, fn (): bool => $structureModel->addPlayerToTeam(
                $championshipId,
                $ownerId,
                $data['team_id'],
                $data['name'],
                $data['role']
            ), 'Jogador adicionado com sucesso.', 'Erro ao adicionar jogador.');
        }

        if ($action === 'update_player_role') {
            [$data, $errors] = $this->validatePlayerRole($_POST);
            $this->runTeamMutation($errors, fn (): bool => $structureModel->updatePlayerRole(
                $championshipId,
                $ownerId,
                $data['team_id'],
                $data['player_id'],
                $data['role']
            ), 'Função do jogador atualizada com sucesso.', 'Erro ao atualizar função do jogador.');
        }

        if ($action === 'delete_team_player') {
            [$data, $errors] = $this->validatePlayerIdentity($_POST);
            $this->runTeamMutation($errors, fn (): bool => $structureModel->deletePlayer(
                $championshipId,
                $ownerId,
                $data['team_id'],
                $data['player_id']
            ), 'Jogador removido com sucesso.', 'Erro ao remover jogador.');
        }

        header('Location: championship.php?id=' . urlencode($championshipId));
        exit;
    }

    private function validateDetails(array $input): array
    {
        $data = [
            'name' => trim((string) ($input['name'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
        ];

        $errors = [];
        $nameLength = function_exists('mb_strlen') ? mb_strlen($data['name']) : strlen($data['name']);

        if ($nameLength < 3 || $nameLength > 100) {
            $errors[] = 'O nome do campeonato deve ter entre 3 e 100 caracteres.';
        }

        return [$data, $errors];
    }

    private function validateTeamName(array $input): array
    {
        $data = [
            'team_id' => trim((string) ($input['team_id'] ?? '')),
            'name' => trim((string) ($input['name'] ?? '')),
        ];

        $errors = [];
        $nameLength = function_exists('mb_strlen') ? mb_strlen($data['name']) : strlen($data['name']);

        if ($data['team_id'] === '') {
            $errors[] = 'Time inválido.';
        }

        if ($nameLength < 2 || $nameLength > 80) {
            $errors[] = 'O nome do time deve ter entre 2 e 80 caracteres.';
        }

        return [$data, $errors];
    }

    private function validateNewPlayer(array $input): array
    {
        $data = [
            'team_id' => trim((string) ($input['team_id'] ?? '')),
            'name' => trim((string) ($input['name'] ?? '')),
            'role' => (string) ($input['role'] ?? 'reserve'),
        ];

        $errors = [];
        $nameLength = function_exists('mb_strlen') ? mb_strlen($data['name']) : strlen($data['name']);

        if ($data['team_id'] === '') {
            $errors[] = 'Time inválido.';
        }

        if ($nameLength < 2 || $nameLength > 80) {
            $errors[] = 'O nome do jogador deve ter entre 2 e 80 caracteres.';
        }

        if (!in_array($data['role'], ['starter', 'reserve'], true)) {
            $errors[] = 'Função do jogador inválida.';
        }

        return [$data, $errors];
    }

    private function validatePlayerRole(array $input): array
    {
        $data = [
            'team_id' => trim((string) ($input['team_id'] ?? '')),
            'player_id' => trim((string) ($input['player_id'] ?? '')),
            'role' => (string) ($input['role'] ?? 'reserve'),
        ];

        $errors = [];

        if ($data['team_id'] === '' || $data['player_id'] === '') {
            $errors[] = 'Jogador inválido.';
        }

        if (!in_array($data['role'], ['starter', 'reserve'], true)) {
            $errors[] = 'Função do jogador inválida.';
        }

        return [$data, $errors];
    }

    private function validatePlayerIdentity(array $input): array
    {
        $data = [
            'team_id' => trim((string) ($input['team_id'] ?? '')),
            'player_id' => trim((string) ($input['player_id'] ?? '')),
        ];

        $errors = [];

        if ($data['team_id'] === '' || $data['player_id'] === '') {
            $errors[] = 'Jogador inválido.';
        }

        return [$data, $errors];
    }

    private function runTeamMutation(array $errors, callable $mutation, string $successMessage, string $errorMessage): void
    {
        if (!empty($errors)) {
            $_SESSION['snack'] = [
                'type' => 'error',
                'message' => $errors[0],
            ];

            return;
        }

        try {
            $updated = $mutation();

            $_SESSION['snack'] = [
                'type' => $updated ? 'success' : 'error',
                'message' => $updated ? $successMessage : $errorMessage,
            ];
        } catch (Throwable $e) {
            $_SESSION['snack'] = [
                'type' => 'error',
                'message' => $errorMessage,
            ];
        }
    }
}
