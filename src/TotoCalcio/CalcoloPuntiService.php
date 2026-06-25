<?php

namespace TotoCalcio;

class CalcoloPuntiService
{
    public function calcolaPuntiColonna($idColonna)
    {
        $pronostici = \database()->fetchArray('
            SELECT pr.*, pa.goal_casa, pa.goal_ospite, pa.stato, pa.id AS id_partita
            FROM totocalcio_pronostici pr
            JOIN totocalcio_partite pa ON pa.id = pr.id_partita
            WHERE pr.id_colonna = '.prepare($idColonna)
        );

        $totale = 0;

        foreach ($pronostici as $pr) {
            if ($pr['stato'] !== 'finished') continue;
            $punti = $this->calcolaPuntiPronostico($pr);
            \database()->update('totocalcio_pronostici', ['punti' => $punti], ['id' => $pr['id']]);
            $totale += $punti;
        }

        \database()->update('totocalcio_colonne', ['punti_totali' => $totale], ['id' => $idColonna]);

        return $totale;
    }

    public function calcolaPuntiConcorso($idConcorso)
    {
        $colonne = \database()->fetchArray('SELECT id FROM totocalcio_colonne WHERE id_concorso = '.prepare($idConcorso));
        $totali = 0;
        foreach ($colonne as $c) {
            $totali += $this->calcolaPuntiColonna($c['id']);
        }
        return $totali;
    }

    public function calcolaPuntiPronostico($pr)
    {
        if ($pr['tipo'] === '1x2') {
            $gc = $pr['goal_casa'];
            $go = $pr['goal_ospite'];
            if ($gc === null || $go === null) return 0;
            $result = $gc > $go ? '1' : ($gc < $go ? '2' : 'X');
            return ($pr['pronostico'] === $result) ? 1 : 0;
        }

        if ($pr['tipo'] === 'risultato_esatto') {
            $gc = $pr['goal_casa'];
            $go = $pr['goal_ospite'];
            if ($gc === null || $go === null) return 0;
            $actual = $gc . '-' . $go;
            return ($pr['pronostico'] === $actual) ? 3 : 0;
        }

        return 0;
    }

    public function calcolaClassificaGenerale()
    {
        return \database()->fetchArray('
            SELECT p.id, p.nome,
                COALESCE(SUM(c.punti_totali), 0) AS totale
            FROM totocalcio_partecipanti p
            LEFT JOIN totocalcio_colonne c ON c.id_partecipante = p.id
            GROUP BY p.id, p.nome
            ORDER BY totale DESC, p.nome ASC
        ');
    }

    public function calcolaClassificaRange($dataInizio, $dataFine = null)
    {
        $where = '1=1';
        $params = [];
        if ($dataInizio) {
            $where .= ' AND co.data_chiusura >= ' . prepare($dataInizio);
        }
        if ($dataFine) {
            $where .= ' AND co.data_chiusura <= ' . prepare($dataFine . ' 23:59:59');
        }

        return \database()->fetchArray('
            SELECT p.id, p.nome,
                COALESCE(SUM(c.punti_totali), 0) AS totale
            FROM totocalcio_partecipanti p
            LEFT JOIN totocalcio_colonne c ON c.id_partecipante = p.id
            LEFT JOIN totocalcio_concorsi co ON co.id = c.id_concorso
            WHERE ' . $where . '
            GROUP BY p.id, p.nome
            ORDER BY totale DESC, p.nome ASC
        ');
    }

    public function determinaVincitoriMiniClassifica($idMiniClassifica)
    {
        $mc = \database()->fetchOne('SELECT * FROM totocalcio_mini_classifiche WHERE id = '.prepare($idMiniClassifica));
        if (!$mc) return [];

        $classifica = $this->calcolaClassificaRange($mc['data_inizio'], $mc['data_fine']);
        $premi = \database()->fetchArray('SELECT * FROM totocalcio_mini_classifiche_premi WHERE id_mini_classifica = '.prepare($idMiniClassifica).' ORDER BY posizione');

        $vincitori = [];
        foreach ($premi as $premio) {
            $pos = (int)$premio['posizione'] - 1;
            if (isset($classifica[$pos])) {
                $vincitori[] = [
                    'id_partecipante' => $classifica[$pos]['id'],
                    'posizione' => $premio['posizione'],
                    'importo' => $premio['importo'],
                ];
            }
        }

        return $vincitori;
    }
}
