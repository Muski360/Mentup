<?php
if (!function_exists('championshipImageSrc')) {
    function championshipImageSrc(string $photoPath, string $basePath): string
    {
        $photoPath = trim($photoPath);

        if ($photoPath === '') {
            return $basePath . 'assets/img/logos/logo_simbolo_1000x1000.png';
        }

        if (str_starts_with($photoPath, 'http://') || str_starts_with($photoPath, 'https://')) {
            return $photoPath;
        }

        $photoPath = preg_replace('#^/?public/#', '', $photoPath);
        $photoPath = ltrim($photoPath, '/');

        return $basePath . $photoPath;
    }
}

$championshipTabs = [
    'in_progress' => 'Em andamento',
    'finished' => 'Finalizados',
];

require __DIR__ . '/layout/header.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/fragments/dashboard-sidebar.php'; ?>

    <section class="championships-main" aria-label="Lista de campeonatos">
        <header class="championships-header">
            <div>
                <h1>Campeonatos</h1>
                <p>Gerencie e acompanhe seus campeonatos</p>
            </div>

            <a class="championships-create" href="create-championship.php">
                <?= mentupIcon('plus') ?>
                Novo campeonato
            </a>
        </header>

        <nav class="championship-tabs" aria-label="Filtro de campeonatos">
            <?php foreach ($championshipTabs as $status => $label): ?>
                <a class="championship-tab<?= $selectedStatus === $status ? ' is-active' : '' ?>"
                    href="championship-list.php?status=<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="championship-list">
            <?php if (empty($championships)): ?>
                <div class="championship-empty">
                    <h2>Nenhum campeonato <?= $selectedStatus === 'finished' ? 'finalizado' : 'em andamento' ?>.</h2>
                    <p>Quando voc&ecirc; criar ou finalizar campeonatos, eles aparecer&atilde;o aqui.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($championships as $championship): ?>
                <?php
                $descriptionLines = array_filter([
                    $championship['modality'] ?? '',
                    $championship['team_mode'] ?? '',
                ]);
                ?>

                <a class="championship-card" href="championship.php?id=<?= urlencode($championship['id']) ?>">
                    <div class="championship-card__identity">
                        <img class="championship-card__photo"
                            src="<?= htmlspecialchars(championshipImageSrc($championship['photo_path'] ?? '', $basePath), ENT_QUOTES, 'UTF-8') ?>"
                            alt="Imagem do campeonato <?= htmlspecialchars($championship['name'], ENT_QUOTES, 'UTF-8') ?>">

                        <div class="championship-card__title">
                            <h2><?= htmlspecialchars($championship['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <?php foreach ($descriptionLines as $line): ?>
                                <span><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="championship-card__metric">
                        <strong><?= str_pad((string) ($championship['total_teams'] ?? 0), 2, '0', STR_PAD_LEFT) ?></strong>
                        <span>Equipes</span>
                    </div>

                    <div class="championship-card__date">
                        <strong><?= htmlspecialchars($championship['start_date'] ?: '--/--/----', ENT_QUOTES, 'UTF-8') ?></strong>
                        <span>Data in&iacute;cio</span>
                    </div>

                    <div class="championship-card__status">
                        <span class="championship-status championship-status--<?= htmlspecialchars($championship['status'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($championship['status_label'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php
require __DIR__ . '/layout/footer.php';
?>
