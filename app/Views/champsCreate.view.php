<?php
require __DIR__ . '/layout/header.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/fragments/dashboard-sidebar.php'; ?>

    <section class="champ-create" aria-label="Criar campeonato">
        <header class="champ-create__header">
            <div class="champ-create__title-row">
                <a class="champ-create__back" href="championship-list.php" aria-label="Voltar para campeonatos"></a>
                <h1>Crie seu campeonato!</h1>
            </div>
            <p>Preencha os dados para criar seu campeonato.</p>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="champ-create-alert" role="alert">
                <?php foreach ($errors as $error): ?>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="champ-create-card" action="create-championship.php" method="post" data-championship-form>
            <section class="champ-form-section">
                <header class="champ-form-section__title">
                    <img src="assets/img/icon/info.svg" alt="" aria-hidden="true">
                    <h2>Informa&ccedil;&otilde;es b&aacute;sicas</h2>
                </header>

                <div class="champ-form-grid champ-form-grid--basic">
                    <label class="champ-field">
                        <span>Nome do campeonato <strong>*</strong></span>
                        <input name="name" type="text" value="<?= htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </label>

                    <label class="champ-field">
                        <span>Modalidade <strong>*</strong></span>
                        <select name="modality" required>
                            <option value="beach_volleyball"<?= $formData['modality'] === 'beach_volleyball' ? ' selected' : '' ?>>V&ocirc;lei de Praia</option>
                        </select>
                    </label>

                    <label class="champ-field champ-field--full">
                        <span>Descri&ccedil;&atilde;o <em>(opcional)</em></span>
                        <textarea name="description"><?= htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </label>
                </div>
            </section>

            <section class="champ-form-section">
                <header class="champ-form-section__title">
                    <img src="assets/img/icon/settings.svg" alt="" aria-hidden="true">
                    <h2>Configura&ccedil;&otilde;es do campeonato</h2>
                </header>

                <div class="champ-form-grid champ-form-grid--settings">
                    <label class="champ-field">
                        <span>Quantidade de times <strong>*</strong></span>
                        <input name="team_count" type="number" min="2" max="64"
                            value="<?= htmlspecialchars((string) $formData['team_count'], ENT_QUOTES, 'UTF-8') ?>"
                            data-team-count required>
                    </label>

                    <label class="champ-field">
                        <span>Formato de campeonato <strong>*</strong></span>
                        <select name="format" required>
                            <option value="groups_and_knockout"<?= $formData['format'] === 'groups_and_knockout' ? ' selected' : '' ?>>Fase de grupos + chaveamento</option>
                            <option value="knockout"<?= $formData['format'] === 'knockout' ? ' selected' : '' ?>>Chaveamento</option>
                            <option value="round_robin"<?= $formData['format'] === 'round_robin' ? ' selected' : '' ?>>Pontos corridos</option>
                        </select>
                    </label>

                    <label class="champ-field">
                        <span>Crit&eacute;rio de vit&oacute;ria <strong>*</strong></span>
                        <select name="best_of" required>
                            <option value="best_of_1"<?= $formData['best_of'] === 'best_of_1' ? ' selected' : '' ?>>Melhor de 1 (set)</option>
                            <option value="best_of_3"<?= $formData['best_of'] === 'best_of_3' ? ' selected' : '' ?>>Melhor de 3 (sets)</option>
                        </select>
                    </label>

                    <label class="champ-field">
                        <span>Jogadores por time <strong>*</strong></span>
                        <select name="team_mode" required>
                            <option value="duo"<?= $formData['team_mode'] === 'duo' ? ' selected' : '' ?>>2 (dupla)</option>
                            <option value="quartet"<?= $formData['team_mode'] === 'quartet' ? ' selected' : '' ?>>4 (quarteto)</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="champ-form-section">
                <div class="champ-teams-head">
                    <header class="champ-form-section__title">
                        <img src="assets/img/icon/team.svg" alt="" aria-hidden="true">
                        <div>
                            <h2>Times do campeonato</h2>
                            <p>Voc&ecirc; pode editar a qualquer momento.</p>
                        </div>
                    </header>

                    <div class="champ-input-mode" aria-label="Modo de cadastro de times">
                        <button class="is-active" type="button">Digitar um de cada vez</button>
                        <button type="button">Digitar tudo de uma vez</button>
                    </div>
                </div>

                <div class="champ-team-list" data-team-list>
                    <?php foreach ($formData['teams'] as $index => $team): ?>
                        <div class="champ-team-row">
                            <label class="champ-field">
                                <span>Time <?= $index + 1 ?>:</span>
                                <input name="team_names[]" type="text" value="<?= htmlspecialchars($team['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                            </label>
                            <label class="champ-field">
                                <span>Jogadores:</span>
                                <input name="team_players[]" type="text" value="<?= htmlspecialchars($team['players_text'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Ana, Bruno">
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <footer class="champ-create-actions">
                <button class="champ-clear" type="reset">Excluir dados</button>
                <button class="champ-submit" type="submit">Criar campeonato!</button>
            </footer>
        </form>
    </section>
</div>

<?php
require __DIR__ . '/layout/footer.php';
?>
