<?php

require_once __DIR__ . '/../Models/ChampsCreateModel.php';
require_once __DIR__ . '/../Services/ChampionshipScheduleGenerator.php';

class ChampsCreateController
{
    private const FORMATS = ['groups_and_knockout', 'knockout', 'round_robin'];
    private const BEST_OF = ['best_of_1', 'best_of_3'];
    private const TEAM_MODES = ['duo', 'quartet'];
    private const MODALITIES = ['beach_volleyball'];

    public function index(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }

        $formData = $this->defaultFormData();
        $errors = [];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            [$formData, $errors] = $this->validate($_POST);

            if (empty($errors)) {
                require __DIR__ . '/../../config/database.php';

                if (!isset($pdo) || !$pdo instanceof PDO) {
                    $errors[] = 'Database connection was not initialized.';
                } else {
                    try {
                        $model = new ChampsCreateModel($pdo);
                        $structureModel = new ChampionshipStructureModel($pdo);
                        $scheduleGenerator = new ChampionshipScheduleGenerator($structureModel);
                        $ownerId = (string) $_SESSION['user_id'];

                        $model->createChampionship(
                            $ownerId,
                            $formData,
                            fn (string $championshipId): bool => $scheduleGenerator->ensureGenerated($championshipId, $ownerId)
                        );

                        $_SESSION['snack'] = [
                            'type' => 'success',
                            'message' => 'Campeonato criado com sucesso.',
                        ];

                        header('Location: championship-list.php');
                        exit;
                    } catch (Throwable $e) {
                        $errors[] = 'Erro ao criar campeonato. ' . $e->getMessage();
                    }
                }
            }
        }

        $pageTitle = 'Criar campeonato - Mentup';
        $basePath = '';
        $pageStyles = ['assets/css/dashboard.css', 'assets/css/championship-create.css'];
        $bodyClass = 'dashboard-page create-championship-page';
        $hideChrome = true;
        $activeMenu = 'championships';
        $dashboardUserName = $_SESSION['user_name'] ?? 'Organizador';

        require __DIR__ . '/../Views/champsCreate.view.php';
    }

    private function defaultFormData(): array
    {
        return [
            'name' => '',
            'modality' => 'beach_volleyball',
            'description' => '',
            'team_count' => 3,
            'format' => 'groups_and_knockout',
            'best_of' => 'best_of_3',
            'team_mode' => 'duo',
            'teams' => [
                ['name' => '', 'players_text' => '', 'players' => []],
                ['name' => '', 'players_text' => '', 'players' => []],
                ['name' => '', 'players_text' => '', 'players' => []],
            ],
        ];
    }

    private function validate(array $input): array
    {
        $teamCount = max(2, min(64, (int) ($input['team_count'] ?? 0)));
        $teamNames = $input['team_names'] ?? [];
        $teamPlayers = $input['team_players'] ?? [];

        $data = [
            'name' => trim((string) ($input['name'] ?? '')),
            'modality' => (string) ($input['modality'] ?? 'beach_volleyball'),
            'description' => trim((string) ($input['description'] ?? '')),
            'team_count' => $teamCount,
            'format' => (string) ($input['format'] ?? 'groups_and_knockout'),
            'best_of' => (string) ($input['best_of'] ?? 'best_of_3'),
            'team_mode' => (string) ($input['team_mode'] ?? 'duo'),
            'teams' => [],
        ];

        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Informe o nome do campeonato.';
        }

        if (!in_array($data['modality'], self::MODALITIES, true)) {
            $errors[] = 'Modalidade inválida.';
        }

        if (!in_array($data['format'], self::FORMATS, true)) {
            $errors[] = 'Formato de campeonato inválido.';
        }

        if (!in_array($data['best_of'], self::BEST_OF, true)) {
            $errors[] = 'Critério de vitória inválido.';
        }

        if (!in_array($data['team_mode'], self::TEAM_MODES, true)) {
            $errors[] = 'Quantidade de jogadores por time inválida.';
        }

        if ($teamCount < 2) {
            $errors[] = 'Informe pelo menos 2 times.';
        }

        for ($index = 0; $index < $teamCount; $index++) {
            $teamName = trim((string) ($teamNames[$index] ?? ''));
            $playersText = trim((string) ($teamPlayers[$index] ?? ''));
            $players = $this->parsePlayers($playersText, $data['team_mode']);

            $data['teams'][] = [
                'name' => $teamName,
                'players_text' => $playersText,
                'players' => $players,
            ];

            if ($teamName === '') {
                $errors[] = 'Informe o nome do time ' . ($index + 1) . '.';
            }
        }

        return [$data, $errors];
    }

    private function parsePlayers(string $playersText, string $teamMode): array
    {
        if ($playersText === '') {
            return [];
        }

        // Cada jogador pode ser digitado separado por virgula: "Ana, Bruno".
        $playerNames = array_values(array_filter(array_map(
            fn (string $player): string => trim($player),
            explode(',', $playersText)
        )));

        $starterLimit = $teamMode === 'quartet' ? 4 : 2;

        return array_map(
            fn (string $name, int $index): array => [
                'name' => $name,
                'role' => $index < $starterLimit ? 'starter' : 'reserve',
            ],
            $playerNames,
            array_keys($playerNames)
        );
    }
}
