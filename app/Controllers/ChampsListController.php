<?php

require_once __DIR__ . '/../Models/ChampsListModel.php';

class ChampsListController
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

        $selectedStatus = $_GET['status'] ?? 'in_progress';

        if (!in_array($selectedStatus, ['in_progress', 'finished'], true)) {
            $selectedStatus = 'in_progress';
        }

        $championshipsModel = new ChampsListModel($pdo);
        $championshipLists = $championshipsModel->getChampionshipLists((string) $_SESSION['user_id']);
        $championships = $championshipLists[$selectedStatus];

        $pageTitle = 'Campeonatos - Mentup';
        $basePath = '';
        $pageStyles = ['assets/css/dashboard.css', 'assets/css/championship.css'];
        $bodyClass = 'dashboard-page championship-page';
        $hideChrome = true;
        $activeMenu = 'championships';
        $dashboardUserName = $_SESSION['user_name'] ?? 'Organizador';

        require __DIR__ . '/../Views/champsList.view.php';
    }
}
