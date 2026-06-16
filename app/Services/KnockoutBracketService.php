<?php

require_once __DIR__ . '/../Models/KnockoutBracketModel.php';

class KnockoutBracketService
{
    public function __construct(private KnockoutBracketModel $model)
    {
    }

    public function syncAfterResult(string $championshipId, string $ownerId): bool
    {
        $context = $this->model->getContext($championshipId, $ownerId);

        if (
            $context === null
            || ($context['status'] ?? '') !== 'in_progress'
            || ($context['format'] ?? '') === 'round_robin'
            || empty($context['knockout_phase_id'])
        ) {
            return false;
        }

        $startedTransaction = false;

        if (!$this->model->inTransaction()) {
            $this->model->beginTransaction();
            $startedTransaction = true;
        }

        try {
            $changed = false;

            if (!$this->model->hasKnockoutMatches($championshipId)) {
                $changed = $this->createInitialRound($championshipId, $context);
            }

            while ($this->advanceLatestCompletedRound($championshipId, $context)) {
                $changed = true;
            }

            if ($startedTransaction) {
                $this->model->commit();
            }

            return $changed;
        } catch (Throwable $e) {
            if ($startedTransaction) {
                $this->model->rollBack();
            }

            throw $e;
        }
    }

    private function createInitialRound(string $championshipId, array $context): bool
    {
        $format = (string) ($context['format'] ?? '');

        if ($format === 'groups_and_knockout') {
            $completion = $this->model->getGroupStageCompletion($championshipId);

            if (!$completion['is_complete']) {
                return false;
            }

            $entrants = $this->model->getQualifiedGroupTeams($championshipId, 2);
        } elseif ($format === 'knockout') {
            $entrants = $this->model->getOrderedTeams($championshipId);
        } else {
            return false;
        }

        return $this->createRoundFromEntrants($championshipId, $context, 1, $entrants);
    }

    private function advanceLatestCompletedRound(string $championshipId, array $context): bool
    {
        $rounds = $this->model->getKnockoutRounds($championshipId);

        if (empty($rounds)) {
            return false;
        }

        $latestRound = $rounds[count($rounds) - 1];

        if (!$this->isRoundComplete($latestRound)) {
            return false;
        }

        $nextRoundNumber = (int) $latestRound['number'] + 1;

        if ($this->model->hasKnockoutRound($championshipId, $nextRoundNumber)) {
            return false;
        }

        $entrants = $this->getNextRoundEntrants($championshipId, $context, $latestRound);

        if (count($entrants) <= 1) {
            return false;
        }

        return $this->createRoundFromEntrants($championshipId, $context, $nextRoundNumber, $entrants);
    }

    private function getNextRoundEntrants(string $championshipId, array $context, array $latestRound): array
    {
        $winners = $this->getRoundWinners($latestRound);

        if ((int) $latestRound['number'] !== 1) {
            return $winners;
        }

        return array_merge(
            $this->getInitialRoundByes($championshipId, $context),
            $winners
        );
    }

    private function getInitialRoundByes(string $championshipId, array $context): array
    {
        $entrants = match ((string) ($context['format'] ?? '')) {
            'groups_and_knockout' => $this->model->getQualifiedGroupTeams($championshipId, 2),
            'knockout' => $this->model->getOrderedTeams($championshipId),
            default => [],
        };

        return $this->buildPlayablePairsAndByes($entrants)['byes'];
    }

    private function createRoundFromEntrants(string $championshipId, array $context, int $roundNumber, array $entrants): bool
    {
        if (count($entrants) <= 1 || $this->model->hasKnockoutRound($championshipId, $roundNumber)) {
            return false;
        }

        $pairs = $this->buildPlayablePairsAndByes($entrants)['pairs'];

        if (empty($pairs)) {
            return false;
        }

        foreach ($pairs as $index => $pair) {
            $this->model->createKnockoutMatch(
                $championshipId,
                (string) $context['knockout_phase_id'],
                $pair[0]['id'],
                $pair[1]['id'],
                $roundNumber,
                $index + 1,
                'Mata-mata - rodada ' . $roundNumber
            );
        }

        return true;
    }

    private function buildPlayablePairsAndByes(array $entrants): array
    {
        $slots = array_values($entrants);
        $bracketSize = $this->nextPowerOfTwo(count($slots));

        while (count($slots) < $bracketSize) {
            $slots[] = null;
        }

        $pairs = [];
        $byes = [];

        for ($index = 0; $index < $bracketSize / 2; $index++) {
            $teamA = $slots[$index];
            $teamB = $slots[$bracketSize - 1 - $index];

            if ($teamA !== null && $teamB !== null) {
                $pairs[] = [$teamA, $teamB];
                continue;
            }

            if ($teamA !== null) {
                $byes[] = $teamA;
            }

            if ($teamB !== null) {
                $byes[] = $teamB;
            }
        }

        return [
            'pairs' => $pairs,
            'byes' => $byes,
        ];
    }

    private function getRoundWinners(array $round): array
    {
        $winners = [];

        foreach (($round['matches'] ?? []) as $match) {
            $winnerId = (string) ($match['winner_team_id'] ?? '');

            if ($winnerId === '') {
                continue;
            }

            $winners[] = [
                'id' => $winnerId,
                'name' => (string) ($match['winner_name'] ?? ''),
            ];
        }

        return $winners;
    }

    private function isRoundComplete(array $round): bool
    {
        $matches = $round['matches'] ?? [];

        if (empty($matches)) {
            return false;
        }

        foreach ($matches as $match) {
            if (($match['status'] ?? '') !== 'completed' || empty($match['winner_team_id'])) {
                return false;
            }
        }

        return true;
    }

    private function nextPowerOfTwo(int $number): int
    {
        $power = 1;

        while ($power < $number) {
            $power *= 2;
        }

        return $power;
    }
}
