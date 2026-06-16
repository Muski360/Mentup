<?php
$completedMatches = $completedMatches ?? [];
$pendingMatches = $pendingMatches ?? [];
$allResultMatches = $allResultMatches ?? [];
$isFinished = ($championship['status'] ?? '') === 'finished';
$bestOf = $championship['best_of'] ?? 'best_of_3';
$setLimit = $bestOf === 'best_of_1' ? 1 : 3;

$matchDate = static function (array $match): string {
    if (($match['status'] ?? '') === 'completed') {
        return $match['played_date'] ?: '--/--/----';
    }

    return $match['scheduled_date'] ?: '--/--/----';
};

$matchTime = static function (array $match): string {
    return $match['scheduled_time'] ?: '';
};

$setsByNumber = static function (array $match): array {
    $sets = [];

    foreach (($match['sets'] ?? []) as $set) {
        $sets[(int) $set['set_number']] = $set;
    }

    return $sets;
};

require __DIR__ . '/layout/header.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/fragments/dashboard-sidebar.php'; ?>

    <section class="results-page" aria-label="Lan&ccedil;ar resultados">
        <?php if (isset($snack) && is_array($snack) && !empty($snack['message'])): ?>
            <div class="snack snack--<?= htmlspecialchars($snack['type'] ?? 'success', ENT_QUOTES, 'UTF-8') ?>"
                role="status" data-snack>
                <?= htmlspecialchars($snack['message'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <header class="results-header">
            <div>
                <h1>Lan&ccedil;ar resultado</h1>
                <div class="results-header__meta" aria-label="Dados do campeonato">
                    <span>
                        <?= mentupIcon('trophy') ?>
                        <?= htmlspecialchars($championship['name'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span aria-hidden="true">&bull;</span>
                    <span>
                        <?= mentupIcon('volleyball') ?>
                        <?= htmlspecialchars($championship['modality'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <button type="button" aria-label="Mais op&ccedil;&otilde;es">
                        <?= mentupIcon('ellipsis') ?>
                    </button>
                </div>
                <p>Registre e edite os resultados das partidas para manter a competi&ccedil;&atilde;o sempre atualizada.</p>
            </div>
        </header>

        <div class="results-layout">
            <div class="results-content">
                <article class="results-card">
                    <header class="results-card__header">
                        <div>
                            <?= mentupIcon('circle-check') ?>
                            <h2>Resultados lan&ccedil;ados</h2>
                            <span><?= count($completedMatches) ?></span>
                        </div>
                    </header>

                    <?php if (empty($completedMatches)): ?>
                        <div class="results-empty">Nenhum resultado lan&ccedil;ado ainda.</div>
                    <?php else: ?>
                        <div class="results-table results-table--completed">
                            <div class="results-table__head" aria-hidden="true">
                                <span>Rodada / Grupo</span>
                                <span>Data</span>
                                <span>Partida</span>
                                <span>Resultado</span>
                                <span>Status</span>
                                <span>A&ccedil;&atilde;o</span>
                            </div>

                            <?php foreach ($completedMatches as $match): ?>
                                <?php $matchLocked = !empty($match['is_locked']); ?>
                                <div class="results-row">
                                    <strong><?= htmlspecialchars($match['phase_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <time>
                                        <?= htmlspecialchars($matchDate($match), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($matchTime($match) !== ''): ?>
                                            <small><?= htmlspecialchars($matchTime($match), ENT_QUOTES, 'UTF-8') ?></small>
                                        <?php endif; ?>
                                    </time>
                                    <div class="results-matchup">
                                        <span><?= htmlspecialchars($match['team_a_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <b>vs</b>
                                        <span><?= htmlspecialchars($match['team_b_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <strong class="results-score"><?= htmlspecialchars($match['score_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <em class="results-status results-status--completed"><?= htmlspecialchars($match['status_label'], ENT_QUOTES, 'UTF-8') ?></em>
                                    <?php if ($isFinished || $matchLocked): ?>
                                        <button class="results-action is-disabled" type="button" disabled>
                                            <?= $matchLocked ? 'Travado' : 'Editar' ?>
                                        </button>
                                    <?php else: ?>
                                        <button class="results-action" type="button"
                                            data-match-result-open="<?= htmlspecialchars($match['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= mentupIcon('pencil') ?>
                                            Editar
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="results-card">
                    <header class="results-card__header">
                        <div>
                            <?= mentupIcon('clock') ?>
                            <h2>Partidas sem resultado</h2>
                            <span><?= count($pendingMatches) ?></span>
                        </div>
                    </header>

                    <?php if (empty($pendingMatches)): ?>
                        <div class="results-empty">Todas as partidas geradas j&aacute; possuem resultado.</div>
                    <?php else: ?>
                        <div class="results-table results-table--pending">
                            <div class="results-table__head" aria-hidden="true">
                                <span>Rodada / Grupo</span>
                                <span>Data</span>
                                <span>Partida</span>
                                <span>Status</span>
                                <span>A&ccedil;&atilde;o</span>
                            </div>

                            <?php foreach ($pendingMatches as $match): ?>
                                <?php $matchLocked = !empty($match['is_locked']); ?>
                                <div class="results-row">
                                    <strong><?= htmlspecialchars($match['phase_label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <time>
                                        <?= htmlspecialchars($matchDate($match), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($matchTime($match) !== ''): ?>
                                            <small><?= htmlspecialchars($matchTime($match), ENT_QUOTES, 'UTF-8') ?></small>
                                        <?php endif; ?>
                                    </time>
                                    <div class="results-matchup">
                                        <span><?= htmlspecialchars($match['team_a_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <b>x</b>
                                        <span><?= htmlspecialchars($match['team_b_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <em class="results-status results-status--pending"><?= htmlspecialchars($match['status_label'], ENT_QUOTES, 'UTF-8') ?></em>
                                    <?php if ($isFinished || $matchLocked): ?>
                                        <button class="results-action is-disabled" type="button" disabled>
                                            <?= $matchLocked ? 'Travado' : 'Lan&ccedil;ar resultado' ?>
                                        </button>
                                    <?php else: ?>
                                        <button class="results-action results-action--primary" type="button"
                                            data-match-result-open="<?= htmlspecialchars($match['id'], ENT_QUOTES, 'UTF-8') ?>">
                                            Lan&ccedil;ar resultado
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>
            </div>

            <aside class="results-side">
                <div class="results-side__badge">
                    <?= mentupIcon('volleyball') ?>
                </div>
                <h2><?= htmlspecialchars($championship['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars($championship['modality'], ENT_QUOTES, 'UTF-8') ?></p>

                <div class="results-summary">
                    <h3>Resumo do campeonato</h3>
                    <dl>
                        <div>
                            <dt>
                                <?= mentupIcon('calendar-days') ?>
                                Total de partidas
                            </dt>
                            <dd><?= htmlspecialchars((string) $championship['total_matches'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt>
                                <?= mentupIcon('circle-check') ?>
                                Resultados lan&ccedil;ados
                            </dt>
                            <dd><?= htmlspecialchars((string) $championship['completed_matches'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt>
                                <?= mentupIcon('clock') ?>
                                Pendentes
                            </dt>
                            <dd><?= htmlspecialchars((string) $championship['pending_matches'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt>Progresso</dt>
                            <dd><?= htmlspecialchars((string) $championship['progress'], ENT_QUOTES, 'UTF-8') ?>%</dd>
                        </div>
                    </dl>
                    <div class="results-progress" aria-hidden="true">
                        <span style="width: <?= (int) $championship['progress'] ?>%"></span>
                    </div>
                </div>

                <div class="results-quick-actions">
                    <h3>A&ccedil;&otilde;es r&aacute;pidas</h3>
                    <a class="results-back" href="championship.php?id=<?= urlencode($championship['id']) ?>">
                        <?= mentupIcon('arrow-left') ?>
                        Voltar
                    </a>
                    <?php if ($isFinished): ?>
                        <button class="results-finish is-disabled" type="button" disabled>Campeonato finalizado</button>
                    <?php else: ?>
                        <button class="results-finish" type="button" data-finish-championship-open>
                            <?= mentupIcon('trophy') ?>
                            Encerrar campeonato
                        </button>
                    <?php endif; ?>
                    <p>Ap&oacute;s encerrar, n&atilde;o ser&aacute; poss&iacute;vel lan&ccedil;ar ou editar resultados.</p>
                </div>
            </aside>
        </div>
    </section>
</div>

<?php foreach ($allResultMatches as $match): ?>
    <?php $matchSets = $setsByNumber($match); ?>
    <div class="match-result-modal" aria-hidden="true"
        data-match-result-modal="<?= htmlspecialchars($match['id'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="match-result-modal__backdrop" data-match-result-close></div>
        <section class="match-result-modal__dialog" role="dialog" aria-modal="true"
            aria-labelledby="match-result-title-<?= htmlspecialchars($match['id'], ENT_QUOTES, 'UTF-8') ?>">
            <header class="match-result-modal__header">
                <div>
                    <h2 id="match-result-title-<?= htmlspecialchars($match['id'], ENT_QUOTES, 'UTF-8') ?>">Lan&ccedil;ar resultado</h2>
                    <p><?= htmlspecialchars($match['phase_label'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <button type="button" aria-label="Fechar" data-match-result-close>
                    <?= mentupIcon('x') ?>
                </button>
            </header>

            <form class="match-result-form" action="championship-results.php?id=<?= urlencode($championship['id']) ?>" method="post">
                <input type="hidden" name="action" value="save_result">
                <input type="hidden" name="match_id" value="<?= htmlspecialchars($match['id'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="match-result-teams">
                    <div>
                        <span><?= mentupIcon('volleyball') ?></span>
                        <strong><?= htmlspecialchars($match['team_a_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <b>vs</b>
                    <div>
                        <span><?= mentupIcon('volleyball') ?></span>
                        <strong><?= htmlspecialchars($match['team_b_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                </div>

                <div class="match-result-sets">
                    <?php for ($setNumber = 1; $setNumber <= $setLimit; $setNumber++): ?>
                        <?php
                        $set = $matchSets[$setNumber] ?? null;
                        $teamAValue = $set ? (string) $set['team_a_points'] : '';
                        $teamBValue = $set ? (string) $set['team_b_points'] : '';
                        $setLabel = $setNumber === 3 ? '3&ordm; set (se necess&aacute;rio)' : $setNumber . '&ordm; set';
                        ?>
                        <label class="match-result-set">
                            <span><?= $setLabel ?></span>
                            <input name="sets[<?= $setNumber ?>][team_a]" type="number" min="0" max="99"
                                value="<?= htmlspecialchars($teamAValue, ENT_QUOTES, 'UTF-8') ?>"<?= $setNumber < 3 ? ' required' : '' ?>>
                            <b>x</b>
                            <input name="sets[<?= $setNumber ?>][team_b]" type="number" min="0" max="99"
                                value="<?= htmlspecialchars($teamBValue, ENT_QUOTES, 'UTF-8') ?>"<?= $setNumber < 3 ? ' required' : '' ?>>
                        </label>
                    <?php endfor; ?>
                </div>

                <label class="match-result-notes">
                    <span>Observa&ccedil;&atilde;o da partida</span>
                    <textarea name="notes" maxlength="500" placeholder="Digite observa&ccedil;&otilde;es, ocorr&ecirc;ncias ou detalhes da partida..."><?= htmlspecialchars($match['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    <small>0/500</small>
                </label>

                <p class="match-result-help">Preencha os pontos de cada set para registrar o resultado final.</p>

                <div class="match-result-actions">
                    <button class="btn btn--ghost" type="button" data-match-result-close>Cancelar</button>
                    <button class="match-result-submit" type="submit">Salvar resultado</button>
                </div>
            </form>
        </section>
    </div>
<?php endforeach; ?>

<div class="finish-championship-modal" aria-hidden="true" data-finish-championship-modal>
    <div class="finish-championship-modal__backdrop" data-finish-championship-close></div>
    <section class="finish-championship-modal__dialog" role="dialog" aria-modal="true"
        aria-labelledby="finish-championship-title" aria-describedby="finish-championship-description">
        <h2 id="finish-championship-title">Encerrar campeonato?</h2>
        <p id="finish-championship-description">Ap&oacute;s encerrar, voc&ecirc; n&atilde;o poder&aacute; lan&ccedil;ar ou editar resultados.</p>

        <form class="finish-championship-modal__actions" action="championship-results.php?id=<?= urlencode($championship['id']) ?>" method="post">
            <input type="hidden" name="action" value="finish">
            <button class="btn btn--ghost finish-championship-modal__cancel" type="button" data-finish-championship-close>Cancelar</button>
            <button class="finish-championship-modal__confirm" type="submit">Encerrar</button>
        </form>
    </section>
</div>

<?php
require __DIR__ . '/layout/footer.php';
?>
