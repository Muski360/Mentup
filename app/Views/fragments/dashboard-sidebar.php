<?php
$activeMenu = $activeMenu ?? 'dashboard';
$dashboardUserName = $dashboardUserName ?? ($_SESSION['user_name'] ?? 'Organizador');
$dashboardInitials = trim((string) $dashboardUserName);

if ($dashboardInitials === '') {
    $dashboardInitials = 'OR';
} else {
    $nameParts = preg_split('/\s+/', $dashboardInitials);
    $firstInitial = substr($nameParts[0] ?? 'O', 0, 1);
    $lastInitial = count($nameParts) > 1 ? substr($nameParts[count($nameParts) - 1], 0, 1) : '';
    $dashboardInitials = strtoupper($firstInitial . $lastInitial);
}

$menuItems = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'href' => 'dashboard.php',
        'icon' => 'assets/img/icon/home.svg',
    ],
    [
        'key' => 'championships',
        'label' => 'Campeonatos',
        'href' => '#',
        'icon' => 'assets/img/icon/icon_trophy.svg',
    ],
    [
        'key' => 'settings',
        'label' => 'Configura&ccedil;&atilde;o',
        'href' => '#',
        'icon' => 'assets/img/icon/settings.svg',
    ],
];
?>

<aside class="dashboard-sidebar" aria-label="Menu do dashboard">
    <a class="dashboard-sidebar__brand" href="dashboard.php" aria-label="Dashboard Mentup">
        <img src="<?= $basePath ?>assets/img/logos/logo_1200x400.png" alt="Mentup">
    </a>

    <nav class="dashboard-nav" aria-label="Navega&ccedil;&atilde;o do painel">
        <?php foreach ($menuItems as $item): ?>
            <a class="dashboard-nav__link<?= $activeMenu === $item['key'] ? ' is-active' : '' ?>"
                href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                <img src="<?= $basePath . $item['icon'] ?>" alt="" aria-hidden="true">
                <span><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="dashboard-sidebar__footer">
        <a class="dashboard-help" href="#">
            <img src="<?= $basePath ?>assets/img/icon/help.svg" alt="" aria-hidden="true">
            <span>Ajuda e Suporte</span>
        </a>

        <div class="dashboard-user">
            <span class="dashboard-user__avatar"><?= htmlspecialchars($dashboardInitials, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="dashboard-user__name"><?= htmlspecialchars($dashboardUserName, ENT_QUOTES, 'UTF-8') ?></span>
            <button class="dashboard-user__logout" type="button" aria-label="Sair da conta" data-logout-open>
                <img src="<?= $basePath ?>assets/img/icon/logout.svg" alt="" aria-hidden="true">
            </button>
        </div>
    </div>
</aside>

<div class="logout-modal" aria-hidden="true" data-logout-modal>
    <div class="logout-modal__backdrop" data-logout-close></div>
    <section class="logout-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="logout-modal-title"
        aria-describedby="logout-modal-description">
        <h2 id="logout-modal-title">Deseja realmente sair?</h2>
        <p id="logout-modal-description">Voc&ecirc; precisar&aacute; entrar novamente para acessar seu dashboard.</p>

        <div class="logout-modal__actions">
            <button class="btn btn--ghost logout-modal__cancel" type="button" data-logout-close>Cancelar</button>
            <a class="btn btn--primary logout-modal__confirm" href="logout.php">Sair da conta</a>
        </div>
    </section>
</div>
