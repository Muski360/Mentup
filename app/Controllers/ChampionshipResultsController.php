<?php

require_once __DIR__ . '/../Models/ChampionshipResultsModel.php';
require_once __DIR__ . '/../Models/KnockoutBracketModel.php';
require_once __DIR__ . '/../Services/ChampionshipScheduleGenerator.php';
require_once __DIR__ . '/../Services/KnockoutBracketService.php';
require_once __DIR__ . '/../Services/MatchResultService.php';

class ChampionshipResultsController
{
    public function index(): void
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

        $ownerId = (string) $_SESSION['user_id'];
        $model = new ChampionshipResultsModel($pdo);
        $bracketModel = new KnockoutBracketModel($pdo);
        $structureModel = new ChampionshipStructureModel($pdo);
        $scheduleGenerator = new ChampionshipScheduleGenerator($structureModel);
        $bracketService = new KnockoutBracketService($bracketModel);
        $resultService = new MatchResultService($model, $bracketModel);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handlePost($model, $resultService, $bracketService, $championshipId, $ownerId);
        }

        $championship = $model->findSummary($championshipId, $ownerId);

        if ($championship === null) {
            header('Location: championship-list.php');
            exit;
        }

        if ($championship['status'] === 'in_progress') {
            try {
                $scheduleGenerator->ensureGenerated($championshipId, $ownerId);
                $championship = $model->findSummary($championshipId, $ownerId) ?? $championship;
            } catch (Throwable $e) {
                $_SESSION['snack'] = [
                    'type' => 'error',
                    'message' => 'Erro ao gerar partidas do campeonato.',
                ];
            }
        }

        $completedMatches = $model->getMatchesByStatus($championshipId, 'completed');
        $pendingMatches = $model->getMatchesByStatus($championshipId, 'scheduled');
        $allResultMatches = array_merge($completedMatches, $pendingMatches);

        $pageTitle = 'Lancar resultado - Mentup';
        $basePath = '';
        $pageStyles = ['assets/css/dashboard.css', 'assets/css/championship-results.css'];
        $bodyClass = 'dashboard-page championship-results-page';
        $hideChrome = true;
        $activeMenu = 'championships';
        $dashboardUserName = $_SESSION['user_name'] ?? 'Organizador';
        $snack = $_SESSION['snack'] ?? null;
        unset($_SESSION['snack']);

        require __DIR__ . '/../Views/championshipResults.view.php';
    }

    private function handlePost(
        ChampionshipResultsModel $model,
        MatchResultService $resultService,
        KnockoutBracketService $bracketService,
        string $championshipId,
        string $ownerId
    ): void {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'save_result') {
            [$saved, $message] = $resultService->save($championshipId, $ownerId, $_POST);

            if ($saved) {
                try {
                    $bracketService->syncAfterResult($championshipId, $ownerId);
                } catch (Throwable $e) {
                    $saved = false;
                    $message = 'Resultado salvo, mas ocorreu erro ao atualizar o chaveamento.';
                }
            }

            $_SESSION['snack'] = [
                'type' => $saved ? 'success' : 'error',
                'message' => $message,
            ];
        }

        if ($action === 'finish') {
            $finished = $model->finish($championshipId, $ownerId);

            $_SESSION['snack'] = [
                'type' => $finished ? 'success' : 'error',
                'message' => $finished ? 'Campeonato encerrado com sucesso.' : 'Erro ao encerrar o campeonato.',
            ];
        }

        header('Location: championship-results.php?id=' . urlencode($championshipId));
        exit;
    }
}
