<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

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
        'value' => 67,
        'detail' => '42 criados no &uacute;ltimo m&ecirc;s.',
        'icon' => 'assets/img/icon/home_trophy.svg',
    ],
    [
        'label' => 'Times',
        'value' => 134,
        'detail' => '67 criados no &uacute;ltimo m&ecirc;s.',
        'icon' => 'assets/img/icon/team.svg',
    ],
    [
        'label' => 'Partidas',
        'value' => 33,
        'detail' => '10 partidas no &uacute;ltimo m&ecirc;s.',
        'icon' => 'assets/img/icon/volleyball.svg',
    ],
];

require __DIR__ . '/../app/Views/layout/header.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/../app/Views/fragments/dashboard-sidebar.php'; ?>

    <section class="dashboard-home" aria-label="Resumo do dashboard">
        <?php if (is_array($snack) && !empty($snack['message'])): ?>
            <div class="snack snack--<?= htmlspecialchars($snack['type'] ?? 'success', ENT_QUOTES, 'UTF-8') ?>" role="status"
                data-snack>
                <?= htmlspecialchars($snack['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-home__content">
            <h1>Ol&aacute;, <?= htmlspecialchars($dashboardUserName, ENT_QUOTES, 'UTF-8') ?>!</h1>

            <div class="dashboard-stats" aria-label="Indicadores principais">
                <?php foreach ($dashboardStats as $stat): ?>
                    <article class="stat-card">
                        <img src="<?= $stat['icon'] ?>" alt="" aria-hidden="true">
                        <div class="stat-card__copy">
                            <h2><?= $stat['label'] ?></h2>
                            <strong><?= $stat['value'] ?></strong>
                            <p><?= $stat['detail'] ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<?php
require __DIR__ . '/../app/Views/layout/footer.php';
?>
