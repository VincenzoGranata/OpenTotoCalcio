<?php

namespace TotoCalcio;

class FotmobService
{
    private $baseSeason;
    private $seasonUrl;

    private $teamNameMap = [
        'Milan' => 'AC Milan',
        'Inter' => 'Inter Milan',
        'Roma' => 'AS Roma',
        'Napoli' => 'SSC Napoli',
        'Lazio' => 'SS Lazio',
        'Monza' => 'AC Monza',
        'Venezia' => 'Venezia FC',
        'Lecce' => 'US Lecce',
    ];

    public function __construct()
    {
        $this->baseSeason = '2026-2027';
        $this->seasonUrl = 'https://www.fotmob.com/leagues/55/overview/' . $this->baseSeason;
    }

    private function fetchFixtures()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->seasonUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            throw new \Exception('Fotmob error: ' . ($error ?: "HTTP $httpCode"));
        }

        if (preg_match('/__NEXT_DATA__.*?>(.*?)<\/script>/s', $response, $m)) {
            $data = json_decode($m[1], true);
            return $data['props']['pageProps']['fixtures']['allMatches'] ?? [];
        }

        throw new \Exception('Fotmob: dati non trovati nella pagina');
    }

    private function mapTeamName($fotmobName)
    {
        return $this->teamNameMap[$fotmobName] ?? $fotmobName;
    }

    private function getTeamLogo($nome)
    {
        $team = \database()->fetchOne('SELECT logo FROM totocalcio_squadre WHERE nome = '.prepare($nome));
        return $team ? $team['logo'] : null;
    }

    public function syncAll()
    {
        $matches = $this->fetchFixtures();

        if (empty($matches)) {
            return 0;
        }

        $rounds = [];
        foreach ($matches as $m) {
            $r = (int)$m['round'];
            if (!isset($rounds[$r])) {
                $rounds[$r] = true;
            }
        }

        $total = 0;

        foreach (array_keys($rounds) as $roundNum) {
            $concorso = \database()->fetchOne('SELECT id FROM totocalcio_concorsi WHERE giornata = '.prepare($roundNum));
            if (!$concorso) {
                $dataChiusura = date('Y-m-d H:i:s', strtotime('next friday 20:00'));
                \database()->insert('totocalcio_concorsi', [
                    'nome' => 'Giornata '.$roundNum,
                    'giornata' => $roundNum,
                    'data_chiusura' => $dataChiusura,
                    'stato' => 'aperto',
                ]);
                $concorsoId = \database()->lastInsertedID();
            } else {
                $concorsoId = $concorso['id'];
            }

            $roundMatches = array_filter($matches, fn($m) => (int)$m['round'] === $roundNum);
            $roundMatches = array_values($roundMatches);

            foreach ($roundMatches as $i => $m) {
                $homeName = $this->mapTeamName($m['home']['name']);
                $awayName = $this->mapTeamName($m['away']['name']);
                $matchId = $m['id'];
                $dateStr = $m['status']['utcTime'] ?? null;
                $date = $dateStr ? date('Y-m-d H:i:s', strtotime($dateStr)) : null;
                $homeLogo = $this->getTeamLogo($homeName);
                $awayLogo = $this->getTeamLogo($awayName);

                $existing = \database()->fetchOne('SELECT id FROM totocalcio_partite WHERE id_api = '.prepare($matchId).' AND id_concorso = '.prepare($concorsoId));
                if ($existing) {
                    \database()->update('totocalcio_partite', [
                        'squadra_casa' => $homeName,
                        'squadra_ospite' => $awayName,
                        'logo_casa' => $homeLogo,
                        'logo_ospite' => $awayLogo,
                        'data_partita' => $date,
                        'stato' => 'scheduled',
                        'minuto' => null,
                    ], ['id' => $existing['id']]);
                } else {
                    \database()->insert('totocalcio_partite', [
                        'id_concorso' => $concorsoId,
                        'id_api' => $matchId,
                        'pannello' => 'obbligatorio',
                        'ordine' => $i + 1,
                        'squadra_casa' => $homeName,
                        'squadra_ospite' => $awayName,
                        'logo_casa' => $homeLogo,
                        'logo_ospite' => $awayLogo,
                        'data_partita' => $date,
                        'stato' => 'scheduled',
                    ]);
                    ++$total;
                }
            }

            usleep(200000);
        }

        return $total;
    }
}
