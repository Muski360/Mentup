<?php

class ChampionshipResultsModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findSummary(string $championshipId, string $ownerId): ?array
    {
        $stmt = $this->pdo->prepare("
            select
                c.id,
                c.owner_id,
                c.name,
                c.format,
                c.best_of,
                c.status,
                to_char(c.created_at, 'DD/MM/YYYY') as created_date,
                (
                    select count(*)
                    from matches m
                    where m.championship_id = c.id
                ) as total_matches,
                (
                    select count(*)
                    from matches m
                    where m.championship_id = c.id
                    and m.status = 'completed'
                ) as completed_matches
            from championships c
            where c.id = :championship_id
            and c.owner_id = :owner_id
            limit 1
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':owner_id' => $ownerId,
        ]);

        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$summary) {
            return null;
        }

        return $this->normalizeSummary($summary);
    }

    public function getMatchesByStatus(string $championshipId, string $status): array
    {
        $stmt = $this->pdo->prepare("
            select
                m.id,
                m.round_number,
                m.match_order,
                m.status,
                m.notes,
                to_char(m.scheduled_at, 'DD/MM/YYYY') as scheduled_date,
                to_char(m.scheduled_at, 'HH24:MI') as scheduled_time,
                to_char(m.played_at, 'DD/MM/YYYY') as played_date,
                p.type as phase_type,
                p.name as phase_name,
                p.phase_order,
                g.name as group_name,
                case
                    when p.type = 'group_stage' then exists (
                        select 1
                        from matches km
                        join phases kp
                            on kp.id = km.phase_id
                            and kp.championship_id = km.championship_id
                        where km.championship_id = m.championship_id
                        and kp.type = 'knockout'
                    )
                    when p.type = 'knockout' then exists (
                        select 1
                        from matches nm
                        join phases np
                            on np.id = nm.phase_id
                            and np.championship_id = nm.championship_id
                        where nm.championship_id = m.championship_id
                        and np.type = 'knockout'
                        and nm.round_number > m.round_number
                    )
                    else false
                end as is_locked,
                ta.id as team_a_id,
                ta.name as team_a_name,
                tb.id as team_b_id,
                tb.name as team_b_name,
                w.name as winner_name,
                coalesce(
                    json_agg(
                        json_build_object(
                            'set_number', ms.set_number,
                            'team_a_points', ms.team_a_points,
                            'team_b_points', ms.team_b_points
                        )
                        order by ms.set_number
                    ) filter (where ms.id is not null),
                    '[]'::json
                ) as sets
            from matches m
            join phases p
                on p.id = m.phase_id
                and p.championship_id = m.championship_id
            join teams ta
                on ta.id = m.team_a_id
                and ta.championship_id = m.championship_id
            join teams tb
                on tb.id = m.team_b_id
                and tb.championship_id = m.championship_id
            left join groups g
                on g.id = m.group_id
                and g.championship_id = m.championship_id
            left join teams w
                on w.id = m.winner_team_id
                and w.championship_id = m.championship_id
            left join match_sets ms
                on ms.match_id = m.id
                and ms.championship_id = m.championship_id
            where m.championship_id = :championship_id
            and m.status = :status
            group by
                m.id,
                p.type,
                p.name,
                p.phase_order,
                g.name,
                ta.id,
                ta.name,
                tb.id,
                tb.name,
                w.name
            order by
                case when m.status = 'completed' then 0 else 1 end,
                m.played_at desc nulls last,
                p.phase_order,
                coalesce(g.name, ''),
                m.round_number,
                m.match_order
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':status' => $status,
        ]);

        $knockoutRoundCount = $this->getExpectedKnockoutRoundCount($championshipId);

        return array_map(
            fn (array $match): array => $this->normalizeMatch($match, $knockoutRoundCount),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function findEditableMatch(string $championshipId, string $ownerId, string $matchId): ?array
    {
        $stmt = $this->pdo->prepare("
            select
                m.id,
                m.championship_id,
                m.team_a_id,
                m.team_b_id,
                c.owner_id,
                c.best_of,
                c.status as championship_status
            from matches m
            join championships c on c.id = m.championship_id
            where m.id = :match_id
            and m.championship_id = :championship_id
            and c.owner_id = :owner_id
            limit 1
        ");

        $stmt->execute([
            ':match_id' => $matchId,
            ':championship_id' => $championshipId,
            ':owner_id' => $ownerId,
        ]);

        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        return $match ?: null;
    }

    public function replaceMatchResult(string $championshipId, string $matchId, array $sets, string $notes): void
    {
        $this->pdo->beginTransaction();

        try {
            $deleteStmt = $this->pdo->prepare("
                delete from match_sets
                where championship_id = :championship_id
                and match_id = :match_id
            ");

            $deleteStmt->execute([
                ':championship_id' => $championshipId,
                ':match_id' => $matchId,
            ]);

            $insertStmt = $this->pdo->prepare("
                insert into match_sets (
                    championship_id,
                    match_id,
                    set_number,
                    team_a_points,
                    team_b_points
                ) values (
                    :championship_id,
                    :match_id,
                    :set_number,
                    :team_a_points,
                    :team_b_points
                )
            ");

            foreach ($sets as $set) {
                $insertStmt->execute([
                    ':championship_id' => $championshipId,
                    ':match_id' => $matchId,
                    ':set_number' => $set['set_number'],
                    ':team_a_points' => $set['team_a_points'],
                    ':team_b_points' => $set['team_b_points'],
                ]);
            }

            $notesStmt = $this->pdo->prepare("
                update matches
                set notes = :notes
                where id = :match_id
                and championship_id = :championship_id
            ");

            $notesStmt->execute([
                ':notes' => $notes !== '' ? $notes : null,
                ':match_id' => $matchId,
                ':championship_id' => $championshipId,
            ]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function finish(string $championshipId, string $ownerId): bool
    {
        $stmt = $this->pdo->prepare("
            update championships
            set status = 'finished'
            where id = :championship_id
            and owner_id = :owner_id
            and status = 'in_progress'
        ");

        $stmt->execute([
            ':championship_id' => $championshipId,
            ':owner_id' => $ownerId,
        ]);

        return $stmt->rowCount() > 0;
    }

    private function normalizeSummary(array $summary): array
    {
        $totalMatches = (int) ($summary['total_matches'] ?? 0);
        $completedMatches = (int) ($summary['completed_matches'] ?? 0);
        $pendingMatches = max(0, $totalMatches - $completedMatches);
        $progress = $totalMatches > 0 ? (int) round(($completedMatches / $totalMatches) * 100) : 0;

        return [
            'id' => (string) ($summary['id'] ?? ''),
            'name' => (string) ($summary['name'] ?? 'Campeonato'),
            'format' => (string) ($summary['format'] ?? ''),
            'format_label' => $this->formatTournamentFormat((string) ($summary['format'] ?? '')),
            'best_of' => (string) ($summary['best_of'] ?? 'best_of_3'),
            'status' => (string) ($summary['status'] ?? ''),
            'created_date' => (string) ($summary['created_date'] ?? ''),
            'modality' => 'Volei de Praia',
            'total_matches' => $totalMatches,
            'completed_matches' => $completedMatches,
            'pending_matches' => $pendingMatches,
            'progress' => $progress,
        ];
    }

    private function normalizeMatch(array $match, int $knockoutRoundCount): array
    {
        $sets = json_decode((string) ($match['sets'] ?? '[]'), true);

        if (!is_array($sets)) {
            $sets = [];
        }

        $sets = array_map(
            fn (array $set): array => [
                'set_number' => (int) ($set['set_number'] ?? 0),
                'team_a_points' => (int) ($set['team_a_points'] ?? 0),
                'team_b_points' => (int) ($set['team_b_points'] ?? 0),
            ],
            $sets
        );

        return [
            'id' => (string) ($match['id'] ?? ''),
            'round_number' => (int) ($match['round_number'] ?? 1),
            'match_order' => (int) ($match['match_order'] ?? 1),
            'court_label' => 'Quadra ' . (int) ($match['match_order'] ?? 1),
            'status' => (string) ($match['status'] ?? 'scheduled'),
            'status_label' => $this->formatMatchStatus((string) ($match['status'] ?? 'scheduled')),
            'notes' => (string) ($match['notes'] ?? ''),
            'scheduled_date' => (string) ($match['scheduled_date'] ?? ''),
            'scheduled_time' => (string) ($match['scheduled_time'] ?? ''),
            'played_date' => (string) ($match['played_date'] ?? ''),
            'phase_type' => (string) ($match['phase_type'] ?? ''),
            'phase_label' => $this->formatPhaseLabel(
                (string) ($match['phase_type'] ?? ''),
                (string) ($match['group_name'] ?? ''),
                (int) ($match['round_number'] ?? 1),
                $knockoutRoundCount
            ),
            'team_a_id' => (string) ($match['team_a_id'] ?? ''),
            'team_a_name' => (string) ($match['team_a_name'] ?? 'Time A'),
            'team_b_id' => (string) ($match['team_b_id'] ?? ''),
            'team_b_name' => (string) ($match['team_b_name'] ?? 'Time B'),
            'winner_name' => (string) ($match['winner_name'] ?? ''),
            'sets' => $sets,
            'score_label' => $this->formatScore($sets, (string) ($match['status'] ?? 'scheduled')),
            'is_locked' => (bool) ($match['is_locked'] ?? false),
        ];
    }

    private function formatScore(array $sets, string $status): string
    {
        if ($status !== 'completed') {
            return 'x';
        }

        $teamAWins = 0;
        $teamBWins = 0;

        foreach ($sets as $set) {
            if ($set['team_a_points'] > $set['team_b_points']) {
                $teamAWins++;
            } elseif ($set['team_b_points'] > $set['team_a_points']) {
                $teamBWins++;
            }
        }

        return $teamAWins . ' x ' . $teamBWins;
    }

    private function formatPhaseLabel(
        string $phaseType,
        string $groupName,
        int $roundNumber,
        int $knockoutRoundCount
    ): string
    {
        if ($phaseType === 'group_stage' && $groupName !== '') {
            return 'Grupo ' . $groupName . ' - R' . $roundNumber;
        }

        return match ($phaseType) {
            'knockout' => $this->formatKnockoutRoundLabel($roundNumber, $knockoutRoundCount),
            'round_robin' => 'Rodada ' . $roundNumber,
            default => 'Rodada ' . $roundNumber,
        };
    }

    private function formatKnockoutRoundLabel(int $roundNumber, int $totalRounds): string
    {
        if ($totalRounds <= 0) {
            return 'Mata-mata - R' . $roundNumber;
        }

        $remainingRounds = max(1, $totalRounds - $roundNumber + 1);

        return match ($remainingRounds) {
            1 => 'Final',
            2 => 'Semifinais',
            3 => 'Quartas',
            4 => 'Oitavas',
            default => 'Rodada ' . $roundNumber,
        };
    }

    private function formatTournamentFormat(string $format): string
    {
        return match ($format) {
            'groups_and_knockout' => 'Fase de grupos + Eliminacao simples',
            'knockout' => 'Eliminacao simples',
            'round_robin' => 'Pontos corridos',
            default => 'Indefinido',
        };
    }

    private function formatMatchStatus(string $status): string
    {
        return match ($status) {
            'completed' => 'Resultado lancado',
            'scheduled' => 'Aguardando resultado',
            default => 'Indefinido',
        };
    }

    private function getExpectedKnockoutRoundCount(string $championshipId): int
    {
        $stmt = $this->pdo->prepare("
            select format
            from championships
            where id = :championship_id
            limit 1
        ");

        $stmt->execute([':championship_id' => $championshipId]);
        $format = (string) $stmt->fetchColumn();

        $entrantCount = match ($format) {
            'knockout' => $this->countChampionshipTeams($championshipId),
            'groups_and_knockout' => $this->countGroupStageQualifiers($championshipId),
            default => 0,
        };

        if ($entrantCount <= 1) {
            return 0;
        }

        return (int) log($this->nextPowerOfTwo($entrantCount), 2);
    }

    private function countChampionshipTeams(string $championshipId): int
    {
        $stmt = $this->pdo->prepare("
            select count(*)::integer
            from teams
            where championship_id = :championship_id
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        return (int) $stmt->fetchColumn();
    }

    private function countGroupStageQualifiers(string $championshipId): int
    {
        $stmt = $this->pdo->prepare("
            select count(*)::integer
            from (
                select
                    row_number() over (
                        partition by g.id
                        order by coalesce(gt.position, 999), t.name, t.id
                    ) as group_rank
                from groups g
                join group_teams gt
                    on gt.group_id = g.id
                    and gt.championship_id = g.championship_id
                join teams t
                    on t.id = gt.team_id
                    and t.championship_id = gt.championship_id
                where g.championship_id = :championship_id
            ) ranked
            where group_rank <= 2
        ");

        $stmt->execute([':championship_id' => $championshipId]);

        return (int) $stmt->fetchColumn();
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
