<?php

/**
 * Sistema di gestione automatica degli stati dei concorsi totocalcio
 *
 * Funzionalità:
 * 1. Chiude automaticamente una giornata quando tutte le partite sono finite
 * 2. Apre automaticamente la giornata successiva quando quella precedente è chiusa
 * 3. Chiude automaticamente una giornata quando inizia la prima partita
 */

include_once __DIR__.'/../../core.php';

function checkConcorsiAutomatic() {
    global $dbo;

    error_log("[TOTOCALCIO] Check automatico stati concorsi");

    // 1. Chiudi giornate completate (tutte partite finished) e apri la successiva
    $giornateAperte = $dbo->fetchArray('
        SELECT c.id, c.giornata, c.stato
        FROM totocalcio_concorsi c
        WHERE c.stato = \'aperto\'
        ORDER BY c.giornata ASC
    ');

    foreach ($giornateAperte as $giornata) {
        // Verifica se tutte le partite sono finite
        $partite = $dbo->fetchArray('
            SELECT COUNT(*) AS total, SUM(CASE WHEN stato = \'finished\' THEN 1 ELSE 0 END) AS finite
            FROM totocalcio_partite
            WHERE id_concorso = '.prepare($giornata['id']).'
        ');

        if ($partite[0]['total'] > 0 && $partite[0]['total'] == $partite[0]['finite']) {
            // Tutte le partite sono finite → chiudi questa giornata
            $dbo->update('totocalcio_concorsi', ['stato' => 'chiuso'], ['id' => $giornata['id']]);
            error_log("[TOTOCALCIO] Giornata {$giornata['giornata']} chiusa (tutte partite finite)");

            // Apri automaticamente la prossima giornata
            $prossima = $dbo->fetchOne('
                SELECT id FROM totocalcio_concorsi
                WHERE giornata > '.prepare($giornata['giornata']).'
                ORDER BY giornata ASC LIMIT 1
            ');

            if ($prossima) {
                $dbo->update('totocalcio_concorsi', ['stato' => 'aperto'], ['id' => $prossima['id']]);
                error_log("[TOTOCALCIO] Giornata successiva aperta automaticamente (ID: {$prossima['id']})");
            }
        }

        // 2. Chiudi automaticamente se è iniziata la prima partita
        $primaPartita = $dbo->fetchOne('
            SELECT data_partita, stato
            FROM totocalcio_partite
            WHERE id_concorso = '.prepare($giornata['id']).'
            ORDER BY data_partita ASC LIMIT 1
        ');

        if ($primaPartita && $primaPartita['data_partita']) {
            $dataPartita = strtotime($primaPartita['data_partita']);
            $oraAdesso = time();

            // Se la prima partita è iniziata, chiudi immediatamente la giornata
            if ($oraAdesso >= $dataPartita && $giornata['stato'] === 'aperto') {
                $dbo->update('totocalcio_concorsi', ['stato' => 'chiuso'], ['id' => $giornata['id']]);
                error_log("[TOTOCALCIO] Giornata {$giornata['giornata']} chiusa (iniziata prima partita)");

                // Apri automaticamente la prossima giornata
                $prossima = $dbo->fetchOne('
                    SELECT id FROM totocalcio_concorsi
                    WHERE giornata > '.prepare($giornata['giornata']).'
                    ORDER BY giornata ASC LIMIT 1
                ');

                if ($prossima) {
                    $dbo->update('totocalcio_concorsi', ['stato' => 'aperto'], ['id' => $prossima['id']]);
                    error_log("[TOTOCALCIO] Giornata successiva aperta automaticamente (ID: {$prossima['id']})");
                }
            }
        }
    }

    error_log("[TOTOCALCIO] Check automatico completato");
}

// Esegui il check
checkConcorsiAutomatic();
