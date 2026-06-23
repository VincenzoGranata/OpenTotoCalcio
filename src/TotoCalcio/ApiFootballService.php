<?php

namespace TotoCalcio;

class ApiFootballService
{
    const API_BASE = 'https://www.thesportsdb.com/api/v1/json/3/';

    private $serieATeams = [
        'AC Milan', 'Inter Milan', 'Juventus', 'AS Roma', 'SS Lazio',
        'SSC Napoli', 'Atalanta', 'Fiorentina', 'Bologna', 'Torino',
        'Udinese', 'Genoa', 'Cagliari', 'Frosinone', 'Sassuolo',
        'Como', 'AC Monza', 'US Lecce', 'Parma', 'Venezia FC',
    ];

    private function request($endpoint)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => self::API_BASE . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new \Exception('TheSportsDB error: ' . ($error ?: "HTTP $httpCode"));
        }

        return json_decode($response, true);
    }

    public function syncTeams()
    {
        $count = 0;
        $seen = [];

        foreach ($this->serieATeams as $nome) {
            if (isset($seen[$nome])) continue;
            $seen[$nome] = true;

            $data = $this->request('searchteams.php?t=' . urlencode($nome));
            $teams = $data['teams'] ?? [];

            $team = null;
            foreach ($teams as $t) {
                $tName = strtolower($t['strTeam'] ?? '');
                $skip = ['youth', 'primavera', 'u19', 'u20', 'u21', 'reserve', 'women', 'femminile'];
                $isSkip = false;
                foreach ($skip as $sk) {
                    if (stripos($tName, $sk) !== false) { $isSkip = true; break; }
                }
                if (!$isSkip) { $team = $t; break; }
            }

            if (!$team) continue;

            $teamId = $team['idTeam'];
            $logo = $team['strBadge'] ?: ($team['strLogo'] ?: null);

            $existing = \database()->fetchOne('SELECT id FROM totocalcio_squadre WHERE id_api = '.prepare($teamId));
            if (!$existing) {
                $existing = \database()->fetchOne('SELECT id FROM totocalcio_squadre WHERE nome = '.prepare($nome));
            }
            if ($existing) {
                \database()->update('totocalcio_squadre', [
                    'id_api' => $teamId,
                    'nome' => $nome,
                    'logo' => $logo,
                ], ['id' => $existing['id']]);
            } else {
                \database()->insert('totocalcio_squadre', [
                    'id_api' => $teamId,
                    'nome' => $nome,
                    'logo' => $logo,
                ]);
                ++$count;
            }

            usleep(300000);
        }

        return $count;
    }

    public function syncPlayers($idSquadra)
    {
        $squadra = \database()->fetchOne('SELECT id_api FROM totocalcio_squadre WHERE id = '.prepare($idSquadra));
        if (!$squadra || !$squadra['id_api']) {
            return 0;
        }

        $data = $this->request('lookup_all_players.php?id=' . $squadra['id_api']);
        $players = $data['player'] ?? [];

        if (empty($players)) {
            return 0;
        }

        $count = 0;
        foreach ($players as $player) {
            $playerName = $player['strPlayer'] ?? '';
            if (empty($playerName)) {
                continue;
            }

            $existing = \database()->fetchOne(
                'SELECT id FROM totocalcio_giocatori WHERE nome = '.prepare($playerName).' AND id_squadra = '.prepare($idSquadra)
            );
            if (!$existing) {
                \database()->insert('totocalcio_giocatori', [
                    'nome' => $playerName,
                    'id_squadra' => $idSquadra,
                    'ruolo' => $this->mapPosition($player['strPosition'] ?? ''),
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

    private function mapPosition($role)
    {
        return match (strtolower($role ?? '')) {
            'goalkeeper' => 'Portiere',
            'defender', 'centre-back', 'left-back', 'right-back' => 'Difensore',
            'midfielder', 'defensive midfielder', 'central midfielder', 'attacking midfielder', 'left midfield', 'right midfield' => 'Centrocampista',
            'forward', 'attacker', 'striker', 'centre-forward', 'winger', 'left winger', 'right winger' => 'Attaccante',
            default => $role,
        };
    }
}
