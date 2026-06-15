<?php

require_once __DIR__ . '/../Models/ChampionshipModel.php';

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

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($model, $championshipId, (string) $_SESSION['user_id']);
        }

        $championship = $model->findByOwner($championshipId, (string) $_SESSION['user_id']);

        if ($championship === null) {
            header('Location: championship-list.php');
            exit;
        }

        $pageTitle = $championship['name'] . ' - Mentup';
        $basePath = '';
        $pageStyles = ['assets/css/dashboard.css', 'assets/css/championship-detail.css'];
        $bodyClass = 'dashboard-page championship-detail-page';
        $hideChrome = true;
        $activeMenu = 'championships';
        $dashboardUserName = $_SESSION['user_name'] ?? 'Organizador';
        $snack = $_SESSION['snack'] ?? null;
        unset($_SESSION['snack']);

        require __DIR__ . '/../Views/championship.view.php';
    }

    private function handlePost(ChampionshipModel $model, string $championshipId, string $ownerId): void
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
}
