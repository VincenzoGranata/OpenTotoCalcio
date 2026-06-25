<?php

include_once __DIR__.'/../../core.php';

// Auto-detect partecipante dall'utente loggato (solo per Totocalcio Giocatori)
if (!empty($user) && empty($id_record)) {
    $gruppo = $dbo->fetchOne('SELECT nome FROM zz_groups WHERE id = '.prepare($user['idgruppo']));
    if ($gruppo && $gruppo['nome'] === 'Totocalcio Giocatori') {
        $record = $dbo->fetchOne('SELECT * FROM totocalcio_partecipanti WHERE id_utente = '.prepare($user['id']));
        if (!$record) {
            $record = $dbo->fetchOne('SELECT * FROM totocalcio_partecipanti WHERE email = '.prepare($user['email']));
            if ($record && empty($record['id_utente'])) {
                $dbo->update('totocalcio_partecipanti', ['id_utente' => $user['id']], ['id' => $record['id']]);
            }
        }
        if ($record) {
            $id_record = $record['id'];
        }
    }
}
