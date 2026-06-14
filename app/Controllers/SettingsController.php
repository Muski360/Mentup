<?php

require_once __DIR__ . '/../Models/SettingsModel.php';

class SettingsController
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

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'delete_account') {
            $this->deleteAccount((string) $_SESSION['user_id']);
        }

        $pageTitle = 'Configuracoes - Mentup';
        $basePath = '';
        $pageStyles = ['assets/css/dashboard.css'];
        $bodyClass = 'dashboard-page';
        $hideChrome = true;
        $activeMenu = 'settings';
        $dashboardUserName = $_SESSION['user_name'] ?? 'Organizador';
        $dashboardUserEmail = $_SESSION['user_email'] ?? 'email@mentup.com';
        $settingsError = $_SESSION['settings_error'] ?? '';
        unset($_SESSION['settings_error']);

        require __DIR__ . '/../Views/settings.view.php';
    }

    private function deleteAccount(string $userId): void
    {
        require __DIR__ . '/../../config/database.php';

        if (!isset($pdo) || !$pdo instanceof PDO) {
            $_SESSION['settings_error'] = 'Could not connect to database';
            header('Location: settings.php');
            exit;
        }

        try {
            $settingsModel = new SettingsModel($pdo);
            $deleted = $settingsModel->deleteUser($userId);

            if (!$deleted) {
                $_SESSION['settings_error'] = 'Não foi possível deletar sua conta';
                header('Location: settings.php');
                exit;
            }

            $this->destroySession();

            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $_SESSION['settings_error'] = 'Erro ao excluir conta.';
            header('Location: settings.php');
            exit;
        }
    }

    private function destroySession(): void
    {
        $_SESSION = [];

        // Remove o cookie da sessao para garantir que o navegador nao continue autenticado.
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
