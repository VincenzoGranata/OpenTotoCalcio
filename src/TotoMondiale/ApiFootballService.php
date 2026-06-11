<?php

namespace TotoMondiale;

class ApiFootballService
{
    private $baseUrl = 'https://worldcup26.ir';

    private $teamsCache;

    public function syncMatches()
    {
        $data = $this->get($this->baseUrl . '/get/games');
        $games = $data['games'] ?? [];
        $teams = $this->fetchTeams();
        $count = 0;

        foreach ($games as $game) {
            $apiId = $game['id'];
            $homeTeam = $game['home_team_name_en'] ?? '';
            $awayTeam = $game['away_team_name_en'] ?? '';
            $homeScore = $game['home_score'] !== null && $game['home_score'] !== '' ? (int) $game['home_score'] : null;
            $awayScore = $game['away_score'] !== null && $game['away_score'] !== '' ? (int) $game['away_score'] : null;

            $homeFlag = $this->getTeamFlag($teams, $game['home_team_id'] ?? null);
            $awayFlag = $this->getTeamFlag($teams, $game['away_team_id'] ?? null);

            $localDate = $game['local_date'] ?? '';
            $startDate = \DateTime::createFromFormat('m/d/Y H:i', $localDate);
            $startDateStr = $startDate ? $startDate->format('Y-m-d H:i:s') : date('Y-m-d H:i:s');

            $finished = strtoupper($game['finished'] ?? 'FALSE') === 'TRUE';
            $timeElapsed = $game['time_elapsed'] ?? 'notstarted';
            $status = $finished ? 'finished' : ($timeElapsed !== 'notstarted' && $timeElapsed !== '' ? 'ongoing' : 'scheduled');
            $minuto = ($status === 'ongoing' && is_numeric($timeElapsed)) ? $timeElapsed : null;

            $girone = $game['group'] ?? '';

            $existing = \database()->fetchOne('SELECT id FROM tot_partite WHERE id_sofascore = '.prepare($apiId));
            if ($existing) {
                \database()->update('tot_partite', [
                    'goal_casa' => $homeScore,
                    'goal_ospite' => $awayScore,
                    'stato' => $status,
                    'minuto' => $minuto,
                    'data_partita' => $startDateStr,
                    'squadra_casa' => $homeTeam,
                    'squadra_ospite' => $awayTeam,
                    'flag_casa' => $homeFlag,
                    'flag_ospite' => $awayFlag,
                    'girone' => $girone,
                ], ['id' => $existing['id']]);
            } else {
                \database()->insert('tot_partite', [
                    'id_sofascore' => $apiId,
                    'girone' => $girone,
                    'squadra_casa' => $homeTeam,
                    'squadra_ospite' => $awayTeam,
                    'flag_casa' => $homeFlag,
                    'flag_ospite' => $awayFlag,
                    'goal_casa' => $homeScore,
                    'goal_ospite' => $awayScore,
                    'data_partita' => $startDateStr,
                    'stato' => $status,
                    'minuto' => $minuto,
                ]);
            }
            ++$count;
        }

        return $count;
    }

    public function updateLiveScores()
    {
        $data = $this->get($this->baseUrl . '/get/games');
        $games = $data['games'] ?? [];
        $updated = 0;

        foreach ($games as $game) {
            $apiId = $game['id'];
            $homeScore = $game['home_score'] !== null && $game['home_score'] !== '' ? (int) $game['home_score'] : null;
            $awayScore = $game['away_score'] !== null && $game['away_score'] !== '' ? (int) $game['away_score'] : null;
            $finished = strtoupper($game['finished'] ?? 'FALSE') === 'TRUE';
            $timeElapsed = $game['time_elapsed'] ?? 'notstarted';
            $status = $finished ? 'finished' : ($timeElapsed !== 'notstarted' && $timeElapsed !== '' ? 'ongoing' : 'scheduled');
            $minuto = ($status === 'ongoing' && is_numeric($timeElapsed)) ? $timeElapsed : null;

            $existing = \database()->fetchOne('SELECT id FROM tot_partite WHERE id_sofascore = '.prepare($apiId));
            if (!$existing) continue;

            \database()->update('tot_partite', [
                'goal_casa' => $homeScore,
                'goal_ospite' => $awayScore,
                'stato' => $status,
                'minuto' => $minuto,
            ], ['id' => $existing['id']]);

            ++$updated;
        }

        return $updated;
    }

    public function calculateAllPoints()
    {
        $finished = \database()->fetchArray('SELECT id, goal_casa, goal_ospite FROM tot_partite WHERE stato = \'finished\' AND goal_casa IS NOT NULL AND goal_ospite IS NOT NULL');
        $count = 0;

        foreach ($finished as $match) {
            $count += $this->calculateMatchPoints($match['id'], $match['goal_casa'], $match['goal_ospite']);
        }

        return $count;
    }

    public function calculateMatchPoints($matchId, $goalCasa, $goalOspite)
    {
        $result = $this->getResultSymbol($goalCasa, $goalOspite);

        $predictions = \database()->fetchArray('SELECT id, pronostico FROM tot_pronostici WHERE id_partita = '.prepare($matchId));

        foreach ($predictions as $pred) {
            $punti = ($pred['pronostico'] === $result) ? 1 : 0;
            \database()->update('tot_pronostici', ['punti' => $punti], ['id' => $pred['id']]);
        }

        return count($predictions);
    }

    public function calculateBonusPoints($vincitoreReale = null, $capocannoniereReale = null)
    {
        $updated = 0;

        if (!empty($vincitoreReale)) {
            $vincitori = \database()->fetchArray('SELECT id, valore FROM tot_bonus WHERE tipo = \'vincente\'');
            foreach ($vincitori as $b) {
                $punti = (strtolower((string) $b['valore']) === strtolower((string) $vincitoreReale)) ? 3 : 0;
                \database()->update('tot_bonus', ['punti' => $punti], ['id' => $b['id']]);
                ++$updated;
            }
        }

        if (!empty($capocannoniereReale)) {
            $capo = \database()->fetchArray('SELECT id, valore FROM tot_bonus WHERE tipo = \'capocannoniere\'');
            foreach ($capo as $b) {
                $punti = (strtolower((string) $b['valore']) === strtolower((string) $capocannoniereReale)) ? 2 : 0;
                \database()->update('tot_bonus', ['punti' => $punti], ['id' => $b['id']]);
                ++$updated;
            }
        }

        return $updated;
    }

    public function getResultSymbol($goalCasa, $goalOspite)
    {
        if ($goalCasa > $goalOspite) return '1';
        if ($goalCasa < $goalOspite) return '2';
        return 'X';
    }

    private function fetchTeams()
    {
        if ($this->teamsCache !== null) {
            return $this->teamsCache;
        }

        $data = $this->get($this->baseUrl . '/get/teams');
        $this->teamsCache = $data['teams'] ?? [];

        return $this->teamsCache;
    }

    private function getTeamFlag($teams, $teamId)
    {
        if (!$teamId) return null;

        foreach ($teams as $team) {
            if (isset($team['id']) && (string) $team['id'] === (string) $teamId) {
                return $team['iso2'] ?? null;
            }
        }

        return null;
    }

    private function get($url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("worldcup26.ir error: HTTP $httpCode");
        }

        return json_decode($response, true) ?: [];
    }
}
