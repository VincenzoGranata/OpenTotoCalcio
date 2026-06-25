<?php

// Per gli utenti "Totocalcio Giocatori", mostra direttamente la loro scheda
if (!empty($user) && !empty($id_module) && empty($id_record)) {
    $gruppo = $dbo->fetchOne('SELECT nome FROM zz_groups WHERE id = '.prepare($user['idgruppo']));
    if ($gruppo && $gruppo['nome'] === 'Totocalcio Giocatori') {
        $partecipante = $dbo->fetchOne('SELECT id FROM totocalcio_partecipanti WHERE id_utente = '.prepare($user['id']));
        if ($partecipante) {
            redirect_url(base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.$partecipante['id']);
            exit;
        }
    }
}
