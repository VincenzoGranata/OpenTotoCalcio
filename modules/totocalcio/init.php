<?php

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM totocalcio_partecipanti WHERE id = '.prepare($id_record));
}

// Auto-detect partecipante per utenti collegati
if (empty($id_record) && !empty($user)) {
    $gruppo = $dbo->fetchOne('SELECT nome FROM zz_groups WHERE id = '.prepare($user['idgruppo']));
    $isGiocatore = $gruppo && $gruppo['nome'] === 'Totocalcio Giocatori';

    // Cerca partecipante collegato all'utente
    $partecipante = $dbo->fetchOne('SELECT id FROM totocalcio_partecipanti WHERE id_utente = '.prepare($user['id']));

    // Fallback: cerca per email se è un giocatore
    if (!$partecipante && $isGiocatore && !empty($user['email'])) {
        $partecipante = $dbo->fetchOne('SELECT id FROM totocalcio_partecipanti WHERE email = '.prepare($user['email']));
        if ($partecipante) {
            $dbo->update('totocalcio_partecipanti', ['id_utente' => $user['id']], ['id' => $partecipante['id']]);
        }
    }

    if ($partecipante) {
        $id_record = $partecipante['id'];
        $record = $dbo->fetchOne('SELECT * FROM totocalcio_partecipanti WHERE id = '.prepare($id_record));
    }
}
