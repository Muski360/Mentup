<?php
require __DIR__ . '/layout/header.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/fragments/dashboard-sidebar.php'; ?>

    <section class="dashboard-home" aria-label="Resumo do dashboard">
        <?php if (is_array($snack) && !empty($snack['message'])): ?>
            <div class="snack snack--<?= htmlspecialchars($snack['type'] ?? 'success', ENT_QUOTES, 'UTF-8') ?>"
                role="status" data-snack>
                <?= htmlspecialchars($snack['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-home__content">
            <h1>Ol&aacute;, <?= htmlspecialchars($dashboardUserName, ENT_QUOTES, 'UTF-8') ?>!</h1>

            <div class="dashboard-stats" aria-label="Indicadores principais">
                <?php foreach ($dashboardStats as $stat): ?>
                    <article class="stat-card">
                        <img src="<?= htmlspecialchars($stat['icon'], ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
                        <div class="stat-card__copy">
                            <h2><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <strong><?= htmlspecialchars((string) $stat['value'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <p><?= $stat['detail'] ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<?php
require __DIR__ . '/layout/footer.php';
?>
