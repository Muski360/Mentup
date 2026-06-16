<?php

require_once __DIR__ . '/../Models/DashboardModel.php';

class DashboardController
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

        require __DIR__ . '/../../config/database.php';

        if (!isset($pdo) || !$pdo instanceof PDO) {
            throw new RuntimeException('Database connection was not initialized.');
        }

        $dashboardModel = new DashboardModel($pdo);
        $stats = $dashboardModel->getStatsByUser((string) $_SESSION['user_id']);

        $pageTitle = 'Dashboard - Mentup';
        $basePath = '';
        $pageStyles = ['assets/css/dashboard.css'];
        $bodyClass = 'dashboard-page';
        $hideChrome = true;
        $activeMenu = 'dashboard';
        $dashboardUserName = $_SESSION['user_name'] ?? 'Organizador';
        $snack = $_SESSION['snack'] ?? null;
        unset($_SESSION['snack']);

        $dashboardStats = [
            [
                'label' => 'Campeonatos',
                'value' => $stats['total_championships'],
                'detail' => $stats['total_championships_month'] . ' criados no &uacute;ltimo m&ecirc;s.',
                'icon' => 'trophy',
            ],
            [
                'label' => 'Times',
                'value' => $stats['total_teams'],
                'detail' => $stats['total_teams_month'] . ' criados no &uacute;ltimo m&ecirc;s.',
                'icon' => 'users-round',
            ],
            [
                'label' => 'Partidas',
                'value' => $stats['total_matches'],
                'detail' => $stats['total_matches_month'] . ' partidas no &uacute;ltimo m&ecirc;s.',
                'icon' => 'volleyball',
            ],
        ];

        require __DIR__ . '/../Views/dashboard.view.php';
    }
}
