<?php

require_once __DIR__ . '/../Models/ChampionshipStructureModel.php';

class ChampionshipScheduleGenerator
{
    public function __construct(private ChampionshipStructureModel $structureModel)
    {
    }

    public function ensureGenerated(string $championshipId, string $ownerId): bool
    {
        $context = $this->structureModel->getGenerationContext($championshipId, $ownerId);

        if ($context === null || $context['status'] !== 'in_progress') {
            return false;
        }

        if ($this->structureModel->hasStructure($championshipId)) {
            return false;
        }

        $teams = $this->structureModel->getTeams($championshipId);

        if (count($teams) < 2) {
            throw new RuntimeException('Nao ha times suficientes para gerar partidas.');
        }

        $startedTransaction = false;

        if (!$this->structureModel->inTransaction()) {
            $this->structureModel->beginTransaction();
            $startedTransaction = true;
        }

        try {
            match ((string) $context['format']) {
                'groups_and_knockout' => $this->generateGroupsAndKnockout($championshipId, $teams),
                'knockout' => $this->generateKnockoutOnly($championshipId, $teams),
                'round_robin' => $this->generateRoundRobinOnly($championshipId, $teams),
                default => throw new RuntimeException('Formato de campeonato invalido.'),
            };

            if ($startedTransaction) {
                $this->structureModel->commit();
            }

            return true;
        } catch (Throwable $e) {
            if ($startedTransaction) {
                $this->structureModel->rollBack();
            }

            throw $e;
        }
    }

    private function generateGroupsAndKnockout(string $championshipId, array $teams): void
    {
        $groupPhaseId = $this->structureModel->createPhase($championshipId, 'group_stage', 'Fase de grupos', 1);
        $this->generateGroups($championshipId, $groupPhaseId, $teams);

        // A fase mata-mata e criada agora, mas as partidas dependem dos classificados dos grupos.
        $this->structureModel->createPhase($championshipId, 'knockout', 'Mata-mata', 2);
    }

    private function generateKnockoutOnly(string $championshipId, array $teams): void
    {
        $phaseId = $this->structureModel->createPhase($championshipId, 'knockout', 'Mata-mata', 1);
        $pairs = $this->buildKnockoutFirstRoundPairs($teams);

        foreach ($pairs as $index => $pair) {
            $this->structureModel->createMatch(
                $championshipId,
                $phaseId,
                null,
                $pair[0]['id'],
                $pair[1]['id'],
                1,
                $index + 1,
                'Primeira rodada do mata-mata'
            );
        }
    }

    private function generateRoundRobinOnly(string $championshipId, array $teams): void
    {
        $phaseId = $this->structureModel->createPhase($championshipId, 'round_robin', 'Todos contra todos', 1);
        $this->generateRoundRobinMatches($championshipId, $phaseId, null, $teams, 'Pontos corridos');
    }

    private function generateGroups(string $championshipId, string $phaseId, array $teams): void
    {
        $groupCount = $this->resolveGroupCount(count($teams));
        $groups = array_fill(0, $groupCount, []);

        foreach ($teams as $index => $team) {
            $groups[$index % $groupCount][] = $team;
        }

        foreach ($groups as $index => $groupTeams) {
            $groupName = $this->groupName($index);
            $groupId = $this->structureModel->createGroup($championshipId, $phaseId, $groupName, $index + 1);

            foreach ($groupTeams as $position => $team) {
                $this->structureModel->addTeamToGroup($championshipId, $groupId, $team['id'], $position + 1);
            }

            if (count($groupTeams) >= 2) {
                $this->generateRoundRobinMatches($championshipId, $phaseId, $groupId, $groupTeams, 'Grupo ' . $groupName);
            }
        }
    }

    private function generateRoundRobinMatches(
        string $championshipId,
        string $phaseId,
        ?string $groupId,
        array $teams,
        string $notes
    ): void {
        $rounds = $this->buildRoundRobinRounds($teams);

        foreach ($rounds as $roundNumber => $pairs) {
            foreach ($pairs as $matchOrder => $pair) {
                $this->structureModel->createMatch(
                    $championshipId,
                    $phaseId,
                    $groupId,
                    $pair[0]['id'],
                    $pair[1]['id'],
                    $roundNumber,
                    $matchOrder + 1,
                    $notes
                );
            }
        }
    }

    private function buildRoundRobinRounds(array $teams): array
    {
        $slots = array_values($teams);

        if (count($slots) % 2 !== 0) {
            $slots[] = null;
        }

        $slotCount = count($slots);
        $rounds = [];

        for ($round = 1; $round < $slotCount; $round++) {
            $roundPairs = [];

            for ($index = 0; $index < $slotCount / 2; $index++) {
                $teamA = $slots[$index];
                $teamB = $slots[$slotCount - 1 - $index];

                if ($teamA !== null && $teamB !== null) {
                    $roundPairs[] = [$teamA, $teamB];
                }
            }

            $rounds[$round] = $roundPairs;

            $fixed = $slots[0];
            $rotating = array_slice($slots, 1);
            $last = array_pop($rotating);
            $slots = array_merge([$fixed, $last], $rotating);
        }

        return $rounds;
    }

    private function buildKnockoutFirstRoundPairs(array $teams): array
    {
        $slots = array_values($teams);
        $bracketSize = $this->nextPowerOfTwo(count($slots));

        while (count($slots) < $bracketSize) {
            $slots[] = null;
        }

        $pairs = [];

        for ($index = 0; $index < $bracketSize / 2; $index++) {
            $teamA = $slots[$index];
            $teamB = $slots[$bracketSize - 1 - $index];

            if ($teamA !== null && $teamB !== null) {
                $pairs[] = [$teamA, $teamB];
            }
        }

        return $pairs;
    }

    private function resolveGroupCount(int $teamCount): int
    {
        return max(1, (int) ceil($teamCount / 4));
    }

    private function nextPowerOfTwo(int $number): int
    {
        $power = 1;

        while ($power < $number) {
            $power *= 2;
        }

        return $power;
    }

    private function groupName(int $index): string
    {
        $letters = range('A', 'Z');

        if ($index < count($letters)) {
            return $letters[$index];
        }

        return 'G' . ($index + 1);
    }
}
