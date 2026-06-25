<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

switch (filter('op')) {
    case 'save_colonna':
        $id_partecipante = filter('id_partecipante') ?: $id_record;
        $id_concorso = filter('id_concorso');
        $pronostici = filter('pronostici', null, true);
        $esatto = filter('esatto', null, true);

        if (empty($id_concorso) || (empty($pronostici) && empty($esatto))) {
            flash()->error(tr('Dati incompleti'));
            break;
        }

        // Verifica che l'utente stia salvando per sé stesso
        if ($id_partecipante != $id_record) {
            flash()->error(tr('Accesso negato'));
            break;
        }

        $concorso = $dbo->fetchOne('SELECT * FROM totocalcio_concorsi WHERE id = '.prepare($id_concorso));
        if (!$concorso || $concorso['stato'] !== 'aperto') {
            flash()->error(tr('Concorso non disponibile'));
            break;
        }

        $existing = $dbo->fetchOne('SELECT id FROM totocalcio_colonne WHERE id_partecipante = '.prepare($id_partecipante).' AND id_concorso = '.prepare($id_concorso));
        if ($existing) {
            flash()->error(tr('Hai già una colonna per questo concorso'));
            break;
        }

        $dbo->insert('totocalcio_colonne', [
            'id_partecipante' => $id_partecipante,
            'id_concorso' => $id_concorso,
        ]);
        $id_colonna = $dbo->lastInsertedID();

        foreach ($pronostici as $id_partita => $pr) {
            if (empty($pr['pronostico'])) continue;
            $tipo = $pr['tipo'] ?? '1x2';
            $dbo->insert('totocalcio_pronostici', [
                'id_colonna' => $id_colonna,
                'id_partita' => $id_partita,
                'tipo' => $tipo,
                'pronostico' => $pr['pronostico'],
            ]);
        }

        // Salva anche il pronostico esatto (se presente)
        if (!empty($esatto)) {
            foreach ($esatto as $id_partita => $pr) {
                if (empty($pr['pronostico'])) continue;
                $tipo = $pr['tipo'] ?? 'risultato_esatto';
                $dbo->insert('totocalcio_pronostici', [
                    'id_colonna' => $id_colonna,
                    'id_partita' => $id_partita,
                    'tipo' => $tipo,
                    'pronostico' => $pr['pronostico'],
                ]);
            }
        }

        flash()->info(tr('Colonna creata con successo!'));
        break;
}
