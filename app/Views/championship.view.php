<?php
$championshipStructure = $championshipStructure ?? [
    'groups' => [],
    'round_robin' => [],
    'knockout_rounds' => [],
    'has_knockout_phase' => false,
    'recent_matches' => [],
    'all_matches' => [],
    'teams' => [],
];
$isRoundRobin = ($championship['format'] ?? '') === 'round_robin';
$standings = $isRoundRobin ? $championshipStructure['round_robin'] : [];
$standingsEmptyMessage = match ($championship['format'] ?? '') {
    'knockout' => 'Este formato n&atilde;o possui fase de grupos.',
    'round_robin' => 'A tabela ainda n&atilde;o foi gerada.',
    default => 'Nenhum grupo criado ainda.',
};

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
                    <button class="championship-detail__teams-edit" type="button" data-team-editor-open>Editar times</button>
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
                                <h2><?= $isRoundRobin ? 'Todos contra todos' : 'Fase de grupos' ?></h2>
                            </div>
                            <button type="button" data-detail-modal-open="standings">Ver todos</button>
                        </header>

                        <?php if ($isRoundRobin && !empty($standings)): ?>
                            <div class="championship-standings">
                                <section class="championship-standing-group">
                                    <h3>Classifica&ccedil;&atilde;o geral</h3>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Pos.</th>
                                                <th>Equipe</th>
                                                <th>P</th>
                                                <th>V</th>
                                                <th>D</th>
                                                <th>Sets</th>
                                                <th>Pts</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($standings as $team): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars((string) $team['position'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars((string) $team['matches_played'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars((string) $team['wins'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars((string) $team['losses'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars($team['sets'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars((string) $team['points'], ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </section>
                            </div>
                        <?php elseif (!$isRoundRobin && !empty($championshipStructure['groups'])): ?>
                            <div class="championship-standings">
                                <?php foreach ($championshipStructure['groups'] as $group): ?>
                                    <section class="championship-standing-group">
                                        <h3>Grupo <?= htmlspecialchars($group['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Pos.</th>
                                                    <th>Equipe</th>
                                                    <th>P</th>
                                                    <th>V</th>
                                                    <th>D</th>
                                                    <th>Sets</th>
                                                    <th>Pts</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($group['teams'] as $team): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars((string) $team['position'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td><?= htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td><?= htmlspecialchars((string) $team['matches_played'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td><?= htmlspecialchars((string) $team['wins'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td><?= htmlspecialchars((string) $team['losses'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td><?= htmlspecialchars($team['sets'], ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td><?= htmlspecialchars((string) $team['points'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </section>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="championship-panel__empty">
                                <?= $standingsEmptyMessage ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="championship-panel">
                        <header class="championship-panel__header">
                            <div>
                                <img src="assets/img/icon/settings.svg" alt="" aria-hidden="true">
                                <h2>Chaveamento</h2>
                            </div>
                            <button type="button" data-detail-modal-open="bracket">Ver inteiro</button>
                        </header>

                        <?php if (!empty($championshipStructure['knockout_rounds'])): ?>
                            <div class="championship-bracket">
                                <?php foreach ($championshipStructure['knockout_rounds'] as $round): ?>
                                    <section class="championship-bracket__round">
                                        <h3><?= htmlspecialchars($round['label'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <?php foreach ($round['matches'] as $match): ?>
                                            <?php
                                                $teamAIsWinner = !empty($match['winner_team_id']) && $match['winner_team_id'] === $match['team_a_id'];
                                                $teamBIsWinner = !empty($match['winner_team_id']) && $match['winner_team_id'] === $match['team_b_id'];
                                            ?>
                                            <div class="championship-bracket__match<?= $match['status'] === 'completed' ? ' is-completed' : '' ?>">
                                                <span class="championship-bracket__team<?= $teamAIsWinner ? ' is-winner' : '' ?>">
                                                    <b><?= htmlspecialchars($match['team_a_name'], ENT_QUOTES, 'UTF-8') ?></b>
                                                    <?php if ($teamAIsWinner): ?>
                                                        <em>Venceu</em>
                                                    <?php endif; ?>
                                                </span>
                                                <strong>vs</strong>
                                                <span class="championship-bracket__team<?= $teamBIsWinner ? ' is-winner' : '' ?>">
                                                    <b><?= htmlspecialchars($match['team_b_name'], ENT_QUOTES, 'UTF-8') ?></b>
                                                    <?php if ($teamBIsWinner): ?>
                                                        <em>Venceu</em>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </section>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($championshipStructure['has_knockout_phase']): ?>
                            <div class="championship-panel__empty">Mata-mata aguardando classificados.</div>
                        <?php else: ?>
                            <div class="championship-panel__empty">Este formato n&atilde;o possui chaveamento.</div>
                        <?php endif; ?>
                    </article>
                </div>

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

                <a class="championship-result" href="championship-results.php?id=<?= urlencode($championship['id']) ?>">
                    <img src="assets/img/icon/home_trophy.svg" alt="" aria-hidden="true">
                    Lancar resultado
                </a>

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

<div class="team-editor-modal" aria-hidden="true" data-team-editor-modal>
    <div class="team-editor-modal__backdrop" data-team-editor-close></div>
    <section class="team-editor-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="team-editor-title">
        <header class="team-editor-modal__header">
            <div>
                <h2 id="team-editor-title">Editar times</h2>
                <p>Altere nomes, jogadores e fun&ccedil;&otilde;es dos atletas.</p>
            </div>
            <button type="button" aria-label="Fechar" data-team-editor-close>&times;</button>
        </header>

        <?php if (empty($championshipStructure['teams'])): ?>
            <div class="team-editor-empty">Nenhum time cadastrado neste campeonato.</div>
        <?php else: ?>
            <div class="team-editor">
                <aside class="team-editor__list" aria-label="Times do campeonato">
                    <?php foreach ($championshipStructure['teams'] as $index => $team): ?>
                        <button class="<?= $index === 0 ? 'is-active' : '' ?>" type="button"
                            data-team-editor-tab="<?= htmlspecialchars($team['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <strong><?= htmlspecialchars($team['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= count($team['players']) ?> jogador<?= count($team['players']) === 1 ? '' : 'es' ?></span>
                        </button>
                    <?php endforeach; ?>
                </aside>

                <div class="team-editor__panels">
                    <?php foreach ($championshipStructure['teams'] as $index => $team): ?>
                        <section class="team-editor-panel<?= $index === 0 ? ' is-active' : '' ?>"
                            data-team-editor-panel="<?= htmlspecialchars($team['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <form class="team-editor-rename" action="championship.php?id=<?= urlencode($championship['id']) ?>" method="post">
                                <input type="hidden" name="action" value="update_team_name">
                                <input type="hidden" name="team_id" value="<?= htmlspecialchars($team['id'], ENT_QUOTES, 'UTF-8') ?>">

                                <label>
                                    <span>Nome do time</span>
                                    <input name="name" type="text" value="<?= htmlspecialchars($team['name'], ENT_QUOTES, 'UTF-8') ?>" required>
                                </label>

                                <button type="submit">Salvar nome</button>
                            </form>

                            <div class="team-editor-players">
                                <h3>Jogadores</h3>

                                <?php if (empty($team['players'])): ?>
                                    <p class="team-editor-players__empty">Nenhum jogador cadastrado neste time.</p>
                                <?php else: ?>
                                    <?php foreach ($team['players'] as $player): ?>
                                        <div class="team-player-row">
                                            <span><?= htmlspecialchars($player['name'], ENT_QUOTES, 'UTF-8') ?></span>

                                            <form action="championship.php?id=<?= urlencode($championship['id']) ?>" method="post">
                                                <input type="hidden" name="action" value="update_player_role">
                                                <input type="hidden" name="team_id" value="<?= htmlspecialchars($team['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="player_id" value="<?= htmlspecialchars($player['id'], ENT_QUOTES, 'UTF-8') ?>">

                                                <select name="role">
                                                    <option value="starter"<?= $player['role'] === 'starter' ? ' selected' : '' ?>>Titular</option>
                                                    <option value="reserve"<?= $player['role'] === 'reserve' ? ' selected' : '' ?>>Reserva</option>
                                                </select>

                                                <button type="submit">Salvar</button>
                                            </form>

                                            <form action="championship.php?id=<?= urlencode($championship['id']) ?>" method="post">
                                                <input type="hidden" name="action" value="delete_team_player">
                                                <input type="hidden" name="team_id" value="<?= htmlspecialchars($team['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="player_id" value="<?= htmlspecialchars($player['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <button class="team-player-row__delete" type="submit">Excluir</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <form class="team-editor-add" action="championship.php?id=<?= urlencode($championship['id']) ?>" method="post">
                                <input type="hidden" name="action" value="add_team_player">
                                <input type="hidden" name="team_id" value="<?= htmlspecialchars($team['id'], ENT_QUOTES, 'UTF-8') ?>">

                                <h3>Adicionar jogador</h3>

                                <div>
                                    <label>
                                        <span>Nome do jogador</span>
                                        <input name="name" type="text" required>
                                    </label>

                                    <label>
                                        <span>Fun&ccedil;&atilde;o</span>
                                        <select name="role">
                                            <option value="reserve">Reserva</option>
                                            <option value="starter">Titular</option>
                                        </select>
                                    </label>

                                    <button type="submit">Adicionar</button>
                                </div>
                            </form>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<div class="championship-data-modal" aria-hidden="true" data-detail-modal="standings">
    <div class="championship-data-modal__backdrop" data-detail-modal-close></div>
    <section class="championship-data-modal__dialog" role="dialog" aria-modal="true"
        aria-labelledby="standings-modal-title">
        <header class="championship-data-modal__header">
            <div>
                <h2 id="standings-modal-title"><?= $isRoundRobin ? 'Tabela todos contra todos' : 'Tabelas da fase de grupos' ?></h2>
                <p><?= htmlspecialchars($championship['name'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <button type="button" aria-label="Fechar" data-detail-modal-close>&times;</button>
        </header>

        <div class="championship-data-modal__body">
            <?php if ($isRoundRobin && !empty($standings)): ?>
                <section class="championship-standing-group championship-standing-group--modal">
                    <h3>Classifica&ccedil;&atilde;o geral</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Pos.</th>
                                <th>Equipe</th>
                                <th>Partidas</th>
                                <th>Vit&oacute;rias</th>
                                <th>Derrotas</th>
                                <th>Sets</th>
                                <th>Pontos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($standings as $team): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) $team['position'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $team['matches_played'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $team['wins'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $team['losses'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($team['sets'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) $team['points'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php elseif (!$isRoundRobin && !empty($championshipStructure['groups'])): ?>
                <div class="championship-modal-grid">
                    <?php foreach ($championshipStructure['groups'] as $group): ?>
                        <section class="championship-standing-group championship-standing-group--modal">
                            <h3>Grupo <?= htmlspecialchars($group['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Pos.</th>
                                        <th>Equipe</th>
                                        <th>Partidas</th>
                                        <th>Vit&oacute;rias</th>
                                        <th>Derrotas</th>
                                        <th>Sets</th>
                                        <th>Pontos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($group['teams'] as $team): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $team['position'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($team['team_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) $team['matches_played'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) $team['wins'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) $team['losses'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($team['sets'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) $team['points'], ENT_QUOTES, 'UTF-8') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="championship-data-modal__empty"><?= $standingsEmptyMessage ?></div>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="championship-data-modal" aria-hidden="true" data-detail-modal="bracket">
    <div class="championship-data-modal__backdrop" data-detail-modal-close></div>
    <section class="championship-data-modal__dialog championship-data-modal__dialog--wide" role="dialog" aria-modal="true"
        aria-labelledby="bracket-modal-title">
        <header class="championship-data-modal__header">
            <div>
                <h2 id="bracket-modal-title">Chaveamento</h2>
                <p><?= htmlspecialchars($championship['name'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <button type="button" aria-label="Fechar" data-detail-modal-close>&times;</button>
        </header>

        <div class="championship-data-modal__body">
            <?php if (!empty($championshipStructure['knockout_rounds'])): ?>
                <div class="championship-bracket championship-bracket--modal">
                    <?php foreach ($championshipStructure['knockout_rounds'] as $round): ?>
                        <section class="championship-bracket__round">
                            <h3><?= htmlspecialchars($round['label'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <?php foreach ($round['matches'] as $match): ?>
                                <?php
                                    $teamAIsWinner = !empty($match['winner_team_id']) && $match['winner_team_id'] === $match['team_a_id'];
                                    $teamBIsWinner = !empty($match['winner_team_id']) && $match['winner_team_id'] === $match['team_b_id'];
                                ?>
                                <div class="championship-bracket__match<?= $match['status'] === 'completed' ? ' is-completed' : '' ?>">
                                    <span class="championship-bracket__team<?= $teamAIsWinner ? ' is-winner' : '' ?>">
                                        <b><?= htmlspecialchars($match['team_a_name'], ENT_QUOTES, 'UTF-8') ?></b>
                                        <?php if ($teamAIsWinner): ?>
                                            <em>Venceu</em>
                                        <?php endif; ?>
                                    </span>
                                    <strong>vs</strong>
                                    <span class="championship-bracket__team<?= $teamBIsWinner ? ' is-winner' : '' ?>">
                                        <b><?= htmlspecialchars($match['team_b_name'], ENT_QUOTES, 'UTF-8') ?></b>
                                        <?php if ($teamBIsWinner): ?>
                                            <em>Venceu</em>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($championshipStructure['has_knockout_phase']): ?>
                <div class="championship-data-modal__empty">Mata-mata aguardando classificados.</div>
            <?php else: ?>
                <div class="championship-data-modal__empty">Este formato n&atilde;o possui chaveamento.</div>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="championship-data-modal" aria-hidden="true" data-detail-modal="matches">
    <div class="championship-data-modal__backdrop" data-detail-modal-close></div>
    <section class="championship-data-modal__dialog championship-data-modal__dialog--wide" role="dialog" aria-modal="true"
        aria-labelledby="matches-modal-title">
        <header class="championship-data-modal__header">
            <div>
                <h2 id="matches-modal-title">Partidas</h2>
                <p><?= htmlspecialchars($championship['name'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <button type="button" aria-label="Fechar" data-detail-modal-close>&times;</button>
        </header>

        <div class="championship-data-modal__body">
            <?php if (!empty($championshipStructure['all_matches'])): ?>
                <div class="championship-recent championship-recent--modal">
                    <?php foreach ($championshipStructure['all_matches'] as $match): ?>
                        <div class="championship-recent__row">
                            <img src="assets/img/icon/volleyball.svg" alt="" aria-hidden="true">
                            <span><?= htmlspecialchars($match['team_a_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= htmlspecialchars($match['score'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars($match['team_b_name'], ENT_QUOTES, 'UTF-8') ?></span>
                            <em><?= htmlspecialchars($match['phase_label'], ENT_QUOTES, 'UTF-8') ?></em>
                            <time><?= htmlspecialchars($match['played_date'] ?: '--/--/----', ENT_QUOTES, 'UTF-8') ?></time>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="championship-data-modal__empty">Nenhuma partida gerada ainda.</div>
            <?php endif; ?>
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
