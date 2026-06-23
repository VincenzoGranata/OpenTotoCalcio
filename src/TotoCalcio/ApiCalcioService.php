<?php

namespace TotoCalcio;

class ApiCalcioService
{
    private $season;
    private $jsonUrl;
    private $jsonData;

    public function __construct($season = null)
    {
        $this->season = $season ?: $this->detectSeason();
        $this->jsonUrl = 'https://raw.githubusercontent.com/openfootball/football.json/master/' . $this->season . '/it.1.json';
    }

    private function detectSeason()
    {
        $year = (int)date('Y');
        $month = (int)date('m');
        if ($month >= 8) {
            return $year . '-' . substr($year + 1, -2);
        }
        return ($year - 1) . '-' . substr($year, -2);
    }

    private function loadJson()
    {
        if ($this->jsonData !== null) {
            return $this->jsonData;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->jsonUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'OpenSTAManager-TotoCalcio/1.0',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new \Exception('football.json download error: ' . $error);
        }

        $data = json_decode($response, true);
        if (empty($data['matches'])) {
            throw new \Exception('football.json: nessuna partita trovata');
        }

        $this->jsonData = $data;
        return $data;
    }

    // ─────────────────────────────────────────────
    // SQUADRE (estratta dal JSON)
    // ─────────────────────────────────────────────
    public function syncTeams()
    {
        $data = $this->loadJson();
        $matches = $data['matches'] ?? [];
        $teamNames = [];

        foreach ($matches as $m) {
            if (!empty($m['team1'])) $teamNames[$m['team1']] = true;
            if (!empty($m['team2'])) $teamNames[$m['team2']] = true;
        }

        $count = 0;
        foreach (array_keys($teamNames) as $nome) {
            $existing = \database()->fetchOne('SELECT id FROM totocalcio_squadre WHERE nome = '.prepare($nome));
            if (!$existing) {
                \database()->insert('totocalcio_squadre', [
                    'nome' => $nome,
                ]);
                ++$count;
            }
        }

        return $count;
    }

    // ─────────────────────────────────────────────
    // GIOCATORI (TheSportsDB)
    // ─────────────────────────────────────────────
    private function getSportsDbTeamId($nome)
    {
        $teamMap = [
            'AC Milan' => 'AC_Milan',
            'FC Internazionale Milano' => 'Inter_Milan',
            'Inter' => 'Inter_Milan',
            'Juventus FC' => 'Juventus_FC',
            'Juventus' => 'Juventus_FC',
            'AS Roma' => 'AS_Roma',
            'SS Lazio' => 'SS_Lazio',
            'SSC Napoli' => 'SSC_Napoli',
            'Napoli' => 'SSC_Napoli',
            'ACF Fiorentina' => 'Fiorentina',
            'Fiorentina' => 'Fiorentina',
            'Atalanta BC' => 'Atalanta_BC',
            'Atalanta' => 'Atalanta_BC',
            'Bologna FC 1909' => 'Bologna_FC_1909',
            'Bologna' => 'Bologna_FC_1909',
            'Torino FC' => 'Torino_FC',
            'Udinese Calcio' => 'Udinese_Calcio',
            'US Sassuolo Calcio' => 'Sassuolo_Calcio',
            'Genoa CFC' => 'Genoa_CFC',
            'Cagliari Calcio' => 'Cagliari_Calcio',
            'Parma Calcio 1913' => 'Parma_Calcio_1913',
            'AC Monza' => 'AC_Monza',
            'US Lecce' => 'US_Lecce',
            'Venezia FC' => 'Venezia_FC',
            'Frosinone Calcio' => 'Frosinone_Calcio',
            'Como 1907' => 'Como_1907',
            'Hellas Verona FC' => 'Hellas_Verona',
            'US Cremonese' => 'Cremonese',
            'AC Pisa 1909' => 'Pisa',
        ];

        foreach ($teamMap as $key => $mapped) {
            if (stripos($nome, $key) !== false || stripos($key, $nome) !== false) {
                return $this->lookupSportsDbTeam($mapped);
            }
        }
        return null;
    }

    private function lookupSportsDbTeam($searchName)
    {
        $url = 'https://www.thesportsdb.com/api/v1/json/3/searchteams.php?t=' . urlencode($searchName);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) return null;
        $data = json_decode($response, true);
        $teams = $data['teams'] ?? [];
        if (empty($teams)) return null;

        $skipKeywords = ['youth', 'primavera', 'u19', 'u20', 'u21', 'reserve', 'women', 'femminile'];
        foreach ($teams as $team) {
            $tName = strtolower($team['strTeam'] ?? '');
            $isSkip = false;
            foreach ($skipKeywords as $sk) {
                if (stripos($tName, $sk) !== false) { $isSkip = true; break; }
            }
            if (!$isSkip) return $team['idTeam'];
        }
        return $teams[0]['idTeam'] ?? null;
    }

    public function syncPlayers($idSquadra)
    {
        $squadra = \database()->fetchOne('SELECT nome FROM totocalcio_squadre WHERE id = '.prepare($idSquadra));
        if (!$squadra) return 0;

        $sportsDbId = $this->getSportsDbTeamId($squadra['nome']);
        if (!$sportsDbId) return 0;

        $url = 'https://www.thesportsdb.com/api/v1/json/3/lookup_all_players.php?id=' . $sportsDbId;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) return 0;
        $data = json_decode($response, true);
        $players = $data['player'] ?? [];
        if (empty($players)) return 0;

        $count = 0;
        foreach ($players as $player) {
            $playerName = $player['strPlayer'] ?? '';
            if (empty($playerName)) continue;

            $existing = \database()->fetchOne('SELECT id FROM totocalcio_giocatori WHERE nome = '.prepare($playerName).' AND id_squadra = '.prepare($idSquadra));
            if (!$existing) {
                \database()->insert('totocalcio_giocatori', [
                    'nome' => $playerName,
                    'id_squadra' => $idSquadra,
                    'ruolo' => $this->mapSportDbRole($player['strPosition'] ?? ''),
                ]);
                ++$count;
            }
        }
        return $count;
    }

    public function syncAllPlayers()
    {
        $squadre = \database()->fetchArray('SELECT id FROM totocalcio_squadre');
        $total = 0;
        foreach ($squadre as $s) {
            $count = $this->syncPlayers($s['id']);
            $total += $count;
            if ($count > 0) {
                usleep(500000);
            }
        }
        return $total;
    }

    private function mapSportDbRole($role)
    {
        return match (strtolower($role ?? '')) {
            'goalkeeper' => 'Portiere',
            'defender', 'centre-back', 'left-back', 'right-back' => 'Difensore',
            'midfielder', 'defensive midfielder', 'central midfielder', 'attacking midfielder', 'left midfield', 'right midfield' => 'Centrocampista',
            'forward', 'attacker', 'striker', 'centre-forward', 'winger', 'left winger', 'right winger' => 'Attaccante',
            default => $role,
        };
    }

    // ─────────────────────────────────────────────
    // PARTITE (football.json)
    // ─────────────────────────────────────────────
    public function syncGiornata($idConcorso, $numeroGiornata)
    {
        $data = $this->loadJson();
        $matches = $data['matches'] ?? [];
        $roundLabel = 'Matchday ' . $numeroGiornata;
        $count = 0;

        $giornataMatches = [];
        foreach ($matches as $m) {
            if (($m['round'] ?? '') === $roundLabel) {
                $giornataMatches[] = $m;
            }
        }

        foreach ($giornataMatches as $m) {
            $homeTeam = $m['team1'] ?? '';
            $awayTeam = $m['team2'] ?? '';
            if (empty($homeTeam) || empty($awayTeam)) continue;

            $dateStr = ($m['date'] ?? '') . ' ' . ($m['time'] ?? '');
            $date = $dateStr ? date('Y-m-d H:i:s', strtotime($dateStr)) : null;

            $score = $m['score'] ?? [];
            $ft = $score['ft'] ?? (is_array($score) ? $score : []);
            $homeScore = isset($ft[0]) ? (int)$ft[0] : null;
            $awayScore = isset($ft[1]) ? (int)$ft[1] : null;

            $stato = ($homeScore !== null && $awayScore !== null) ? 'finished' : 'scheduled';

            $matchId = crc32($roundLabel . $homeTeam . $awayTeam);

            $existing = \database()->fetchOne('SELECT id FROM totocalcio_partite WHERE id_api = '.prepare($matchId).' AND id_concorso = '.prepare($idConcorso));
            if ($existing) {
                \database()->update('totocalcio_partite', [
                    'goal_casa' => $homeScore,
                    'goal_ospite' => $awayScore,
                    'stato' => $stato,
                    'data_partita' => $date,
                ], ['id' => $existing['id']]);
            } else {
                \database()->insert('totocalcio_partite', [
                    'id_concorso' => $idConcorso,
                    'id_api' => $matchId,
                    'pannello' => 'obbligatorio',
                    'ordine' => 0,
                    'squadra_casa' => $homeTeam,
                    'squadra_ospite' => $awayTeam,
                    'goal_casa' => $homeScore,
                    'goal_ospite' => $awayScore,
                    'data_partita' => $date,
                    'stato' => $stato,
                ]);
                ++$count;
            }
        }

        return $count;
    }

    // ─────────────────────────────────────────────
    // RISULTATI (football.json)
    // ─────────────────────────────────────────────
    public function updateScores($idConcorso)
    {
        $concorso = \database()->fetchOne('SELECT giornata FROM totocalcio_concorsi WHERE id = '.prepare($idConcorso));
        if (!$concorso) return 0;

        $data = $this->loadJson();
        $matches = $data['matches'] ?? [];
        $roundLabel = 'Matchday ' . $concorso['giornata'];
        $updated = 0;

        foreach ($matches as $m) {
            if (($m['round'] ?? '') !== $roundLabel) continue;

            $homeTeam = $m['team1'] ?? '';
            $awayTeam = $m['team2'] ?? '';
            if (empty($homeTeam) || empty($awayTeam)) continue;

            $score = $m['score'] ?? [];
            $ft = $score['ft'] ?? (is_array($score) ? $score : []);
            $homeScore = isset($ft[0]) ? (int)$ft[0] : null;
            $awayScore = isset($ft[1]) ? (int)$ft[1] : null;

            $matchId = crc32($roundLabel . $homeTeam . $awayTeam);
            $stato = ($homeScore !== null && $awayScore !== null) ? 'finished' : 'scheduled';

            $partita = \database()->fetchOne('SELECT id FROM totocalcio_partite WHERE id_api = '.prepare($matchId).' AND id_concorso = '.prepare($idConcorso));
            if (!$partita) continue;

            \database()->update('totocalcio_partite', [
                'goal_casa' => $homeScore,
                'goal_ospite' => $awayScore,
                'stato' => $stato,
            ], ['id' => $partita['id']]);

            ++$updated;
        }

        return $updated;
    }

    public function syncMarcatori($idPartita, $fixture)
    {
        // football.json non fornice marcatori. Vanno inseriti manualmente.
        return 0;
    }
}
