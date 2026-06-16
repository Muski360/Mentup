<?php
$settingsInitials = trim((string) $dashboardUserName);

if ($settingsInitials === '') {
    $settingsInitials = 'OR';
} else {
    $settingsNameParts = preg_split('/\s+/', $settingsInitials);
    $settingsFirstInitial = substr($settingsNameParts[0] ?? 'O', 0, 1);
    $settingsLastInitial = count($settingsNameParts) > 1 ? substr($settingsNameParts[count($settingsNameParts) - 1], 0, 1) : '';
    $settingsInitials = strtoupper($settingsFirstInitial . $settingsLastInitial);
}

$settingsPlaceholders = [
    [
        'title' => 'Notifica&ccedil;&otilde;es',
        'description' => 'Receba avisos sobre partidas, equipes e atualiza&ccedil;&otilde;es dos campeonatos.',
        'status' => 'Em breve',
    ],
    [
        'title' => 'Privacidade do perfil',
        'description' => 'Controle como seu nome aparece para times e participantes.',
        'status' => 'Em breve',
    ],
    [
        'title' => 'Idioma da plataforma',
        'description' => 'Escolha o idioma principal usado na sua conta Mentup.',
        'status' => 'Em breve',
    ],
];

require __DIR__ . '/layout/header.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/fragments/dashboard-sidebar.php'; ?>

    <section class="settings-page" aria-label="Configura&ccedil;&otilde;es da conta">
        <?php if (!empty($settingsError)): ?>
            <div class="snack snack--error" role="alert" data-snack>
                <?= htmlspecialchars($settingsError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <header class="settings-header">
            <div class="settings-profile">
                <span
                    class="settings-profile__avatar"><?= htmlspecialchars($settingsInitials, ENT_QUOTES, 'UTF-8') ?></span>

                <div class="settings-profile__copy">
                    <h1><?= htmlspecialchars($dashboardUserName, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p><?= htmlspecialchars($dashboardUserEmail, ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <button class="settings-edit" type="button" aria-label="Editar perfil">
                    <?= mentupIcon('pencil') ?>
                </button>
            </div>

            <button class="settings-logout" type="button" aria-label="Sair da conta" data-logout-open>
                <?= mentupIcon('log-out') ?>
            </button>
        </header>

        <div class="settings-options" aria-label="Op&ccedil;&otilde;es de configura&ccedil;&atilde;o">
            <?php foreach ($settingsPlaceholders as $setting): ?>
                <article class="settings-option">
                    <div>
                        <h2><?= $setting['title'] ?></h2>
                        <p><?= $setting['description'] ?></p>
                    </div>
                    <span><?= $setting['status'] ?></span>
                </article>
            <?php endforeach; ?>
        </div>

        <button class="settings-delete" type="button" data-delete-account-open>Excluir conta</button>
    </section>
</div>

<div class="delete-account-modal" aria-hidden="true" data-delete-account-modal>
    <div class="delete-account-modal__backdrop" data-delete-account-close></div>
    <section class="delete-account-modal__dialog" role="dialog" aria-modal="true"
        aria-labelledby="delete-account-title" aria-describedby="delete-account-description">
        <h2 id="delete-account-title">Excluir conta?</h2>
        <p id="delete-account-description">Essa a&ccedil;&atilde;o remove sua conta permanentemente e n&atilde;o pode ser desfeita.</p>

        <form class="delete-account-modal__actions" action="settings.php" method="post">
            <input type="hidden" name="action" value="delete_account">
            <button class="btn btn--ghost delete-account-modal__cancel" type="button"
                data-delete-account-close>Cancelar</button>
            <button class="btn delete-account-modal__confirm" type="submit">Excluir conta</button>
        </form>
    </section>
</div>

<?php
require __DIR__ . '/layout/footer.php';
?>
