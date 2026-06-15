<?php
require __DIR__ . '/layout/header.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/fragments/dashboard-sidebar.php'; ?>

    <section class="championship-detail" aria-label="Detalhes do campeonato">
        <?php if (isset($snack) && is_array($snack) && !empty($snack['message'])): ?>
            <div class="snack snack--<?= htmlspecialchars($snack['type'] ?? 'success', ENT_QUOTES, 'UTF-8') ?>"
                role="status" data-snack>
                <?= htmlspecialchars($snack['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <header class="championship-detail__header">
            <div>
                <div class="championship-detail__title-row">
                    <a class="championship-detail__back" href="championship-list.php" aria-label="Voltar para campeonatos"></a>
                    <h1><?= htmlspecialchars($championship['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <button class="championship-detail__more" type="button" aria-label="Editar campeonato" data-championship-edit-open>...</button>
                </div>

                <div class="championship-detail__meta" aria-label="Informacoes do campeonato">
                    <span>
                        <img src="assets/img/icon/volleyball.svg" alt="" aria-hidden="true">
                        <?= htmlspecialchars($championship['modality'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span>
                        <img src="assets/img/icon/calendar.svg" alt="" aria-hidden="true">
                        Criado em <?= htmlspecialchars($championship['created_date'] ?: '--/--/----', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>
        </header>

        <div class="championship-detail__layout">
            <div class="championship-detail__content">
                <article class="championship-summary">
                    <h2>Resumo do campeonato</h2>
                    <div class="championship-summary__metric">
                        <span class="championship-summary__icon">
                            <img src="assets/img/icon/team.svg" alt="" aria-hidden="true">
                        </span>
                        <div>
                            <strong><?= htmlspecialchars((string) $championship['total_teams'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <p>Equipes no total</p>
                        </div>
                    </div>
                </article>

                <div class="championship-panels">
                    <article class="championship-panel">
                        <header class="championship-panel__header">
                            <div>
                                <img src="assets/img/icon/team.svg" alt="" aria-hidden="true">
                                <h2>Fase de grupos</h2>
                            </div>
                            <button type="button">Ver todos</button>
                        </header>
                        <div class="championship-panel__empty">Nenhum grupo criado ainda.</div>
                    </article>

                    <article class="championship-panel">
                        <header class="championship-panel__header">
                            <div>
                                <img src="assets/img/icon/settings.svg" alt="" aria-hidden="true">
                                <h2>Chaveamento</h2>
                            </div>
                            <button type="button">Ver inteiro</button>
                        </header>
                        <div class="championship-panel__empty">Nenhum chaveamento gerado ainda.</div>
                    </article>
                </div>

                <article class="championship-panel championship-panel--wide">
                    <header class="championship-panel__header">
                        <div>
                            <img src="assets/img/icon/clock-lines.svg" alt="" aria-hidden="true">
                            <h2>Partidas recentes</h2>
                        </div>
                        <button type="button">Ver todos</button>
                    </header>
                    <div class="championship-panel__empty">Nenhum resultado lancado ainda.</div>
                </article>
            </div>

            <aside class="championship-side">
                <?php if ($championship['status'] === 'finished'): ?>
                    <button class="championship-finish is-disabled" type="button" disabled>Campeonato finalizado</button>
                <?php else: ?>
                    <button class="championship-finish" type="button" data-finish-championship-open>
                        <img src="assets/img/icon/icon_trophy.svg" alt="" aria-hidden="true">
                        Encerrar campeonato
                    </button>
                <?php endif; ?>

                <button class="championship-result" type="button">
                    <img src="assets/img/icon/home_trophy.svg" alt="" aria-hidden="true">
                    Lancar resultado
                </button>

                <div class="championship-side__about">
                    <h2>
                        <span>i</span>
                        Sobre o campeonato
                    </h2>

                    <dl>
                        <div>
                            <dt>
                                <img src="assets/img/icon/calendar.svg" alt="" aria-hidden="true">
                                Criado em
                            </dt>
                            <dd><?= htmlspecialchars($championship['created_date'] ?: '--/--/----', ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt>
                                <img src="assets/img/icon/team.svg" alt="" aria-hidden="true">
                                Organizador
                            </dt>
                            <dd><?= htmlspecialchars($dashboardUserName, ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt>
                                <img src="assets/img/icon/volleyball.svg" alt="" aria-hidden="true">
                                Modalidade
                            </dt>
                            <dd><?= htmlspecialchars($championship['modality'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt>
                                <img src="assets/img/icon/icon_trophy.svg" alt="" aria-hidden="true">
                                Formato
                            </dt>
                            <dd><?= htmlspecialchars($championship['format_label'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </div>
    </section>
</div>

<div class="championship-edit-modal" aria-hidden="true" data-championship-edit-modal>
    <div class="championship-edit-modal__backdrop" data-championship-edit-close></div>
    <section class="championship-edit-modal__dialog" role="dialog" aria-modal="true"
        aria-labelledby="championship-edit-title">
        <h2 id="championship-edit-title">Editar campeonato</h2>
        <p>Atualize as principais informa&ccedil;&otilde;es do campeonato.</p>

        <form class="championship-edit-form" action="championship.php?id=<?= urlencode($championship['id']) ?>" method="post">
            <input type="hidden" name="action" value="update_details">

            <label class="championship-edit-field">
                <span>Nome do campeonato</span>
                <input name="name" type="text" value="<?= htmlspecialchars($championship['name'], ENT_QUOTES, 'UTF-8') ?>" required>
            </label>

            <label class="championship-edit-field">
                <span>Descri&ccedil;&atilde;o</span>
                <textarea name="description"><?= htmlspecialchars($championship['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>

            <div class="championship-edit-upload" aria-label="Upload de foto do campeonato">
                <span>Foto do campeonato</span>
                <div>
                    <strong>Upload de foto</strong>
                    <p>Em breve voc&ecirc; poder&aacute; enviar a imagem do campeonato por aqui.</p>
                    <button type="button" disabled>Selecionar arquivo</button>
                </div>
            </div>

            <div class="championship-edit-modal__actions">
                <button class="btn btn--ghost championship-edit-modal__cancel" type="button" data-championship-edit-close>Cancelar</button>
                <button class="championship-edit-modal__confirm" type="submit">Salvar altera&ccedil;&otilde;es</button>
            </div>
        </form>
    </section>
</div>

<div class="finish-championship-modal" aria-hidden="true" data-finish-championship-modal>
    <div class="finish-championship-modal__backdrop" data-finish-championship-close></div>
    <section class="finish-championship-modal__dialog" role="dialog" aria-modal="true"
        aria-labelledby="finish-championship-title" aria-describedby="finish-championship-description">
        <h2 id="finish-championship-title">Encerrar campeonato?</h2>
        <p id="finish-championship-description">Essa ação pode ser desfeita a qualquer momento.</p>

        <form class="finish-championship-modal__actions" action="championship.php?id=<?= urlencode($championship['id']) ?>" method="post">
            <input type="hidden" name="action" value="finish">
            <button class="btn btn--ghost finish-championship-modal__cancel" type="button" data-finish-championship-close>Cancelar</button>
            <button class="finish-championship-modal__confirm" type="submit">Encerrar</button>
        </form>
    </section>
</div>

<?php
require __DIR__ . '/layout/footer.php';
?>
