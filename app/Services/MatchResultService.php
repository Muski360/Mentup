<?php

require_once __DIR__ . '/../Models/ChampionshipResultsModel.php';
require_once __DIR__ . '/../Models/KnockoutBracketModel.php';

class MatchResultService
{
    public function __construct(
        private ChampionshipResultsModel $model,
        private ?KnockoutBracketModel $bracketModel = null
    )
    {
    }

    public function save(string $championshipId, string $ownerId, array $input): array
    {
        $matchId = trim((string) ($input['match_id'] ?? ''));

        if ($matchId === '') {
            return [false, 'Partida invalida.'];
        }

        $match = $this->model->findEditableMatch($championshipId, $ownerId, $matchId);

        if ($match === null) {
            return [false, 'Partida nao encontrada.'];
        }

        if (($match['championship_status'] ?? '') !== 'in_progress') {
            return [false, 'Campeonato finalizado nao permite editar resultados.'];
        }

        if ($this->bracketModel !== null && $this->bracketModel->isMatchResultLocked($championshipId, $matchId)) {
            return [false, 'Este resultado ja alimentou o chaveamento e nao pode ser editado.'];
        }

        [$sets, $error] = $this->validateSets($input, (string) ($match['best_of'] ?? 'best_of_3'));

        if ($error !== null) {
            return [false, $error];
        }

        $notes = trim((string) ($input['notes'] ?? ''));

        try {
            $this->model->replaceMatchResult($championshipId, $matchId, $sets, $notes);
        } catch (Throwable $e) {
            return [false, 'Erro ao salvar resultado. Verifique os pontos dos sets.'];
        }

        return [true, 'Resultado salvo com sucesso.'];
    }

    private function validateSets(array $input, string $bestOf): array
    {
        $rawSets = is_array($input['sets'] ?? null) ? $input['sets'] : [];
        $maxSet = $bestOf === 'best_of_1' ? 1 : 3;
        $sets = [];

        for ($setNumber = 1; $setNumber <= $maxSet; $setNumber++) {
            $teamAInput = trim((string) ($rawSets[$setNumber]['team_a'] ?? ''));
            $teamBInput = trim((string) ($rawSets[$setNumber]['team_b'] ?? ''));

            if ($teamAInput === '' && $teamBInput === '') {
                continue;
            }

            if ($teamAInput === '' || $teamBInput === '') {
                return [[], 'Preencha os dois pontos do set ' . $setNumber . '.'];
            }

            if (!ctype_digit($teamAInput) || !ctype_digit($teamBInput)) {
                return [[], 'Os pontos devem ser numeros inteiros.'];
            }

            $teamAPoints = (int) $teamAInput;
            $teamBPoints = (int) $teamBInput;
            $setError = $this->validateSetScore($setNumber, $teamAPoints, $teamBPoints);

            if ($setError !== null) {
                return [[], $setError];
            }

            $sets[$setNumber] = [
                'set_number' => $setNumber,
                'team_a_points' => $teamAPoints,
                'team_b_points' => $teamBPoints,
            ];
        }

        if ($bestOf === 'best_of_1') {
            if (!isset($sets[1])) {
                return [[], 'Informe o resultado do 1o set.'];
            }

            return [array_values($sets), null];
        }

        if (!isset($sets[1], $sets[2])) {
            return [[], 'Informe pelo menos os dois primeiros sets.'];
        }

        $teamAWins = 0;
        $teamBWins = 0;

        foreach ([1, 2] as $setNumber) {
            if ($sets[$setNumber]['team_a_points'] > $sets[$setNumber]['team_b_points']) {
                $teamAWins++;
            } else {
                $teamBWins++;
            }
        }

        if ($teamAWins === 1 && $teamBWins === 1) {
            if (!isset($sets[3])) {
                return [[], 'Informe o 3o set para desempatar a partida.'];
            }

            if ($sets[3]['team_a_points'] > $sets[3]['team_b_points']) {
                $teamAWins++;
            } else {
                $teamBWins++;
            }

            return [array_values($sets), null];
        }

        unset($sets[3]);

        return [array_values($sets), null];
    }

    private function validateSetScore(int $setNumber, int $teamAPoints, int $teamBPoints): ?string
    {
        if ($teamAPoints < 0 || $teamAPoints > 99 || $teamBPoints < 0 || $teamBPoints > 99) {
            return 'Os pontos devem ficar entre 0 e 99.';
        }

        if ($teamAPoints === $teamBPoints) {
            return 'Um set nao pode terminar empatado.';
        }

        $winnerPoints = max($teamAPoints, $teamBPoints);
        $loserPoints = min($teamAPoints, $teamBPoints);
        $targetPoints = $setNumber === 3 ? 15 : 21;

        if ($winnerPoints < $targetPoints) {
            return 'O set ' . $setNumber . ' precisa terminar com pelo menos ' . $targetPoints . ' pontos para o vencedor.';
        }

        if (($winnerPoints - $loserPoints) < 2) {
            return 'O vencedor do set precisa ter pelo menos 2 pontos de diferenca.';
        }

        return null;
    }
}
