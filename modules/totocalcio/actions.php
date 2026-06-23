<?php

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $nome = filter('nome_add');
        $email = filter('email_add');
        if (empty($nome)) {
            flash()->error(tr('Il nome è obbligatorio'));
        } else {
            $dbo->insert('totocalcio_partecipanti', [
                'nome' => $nome,
                'email' => $email ?: null,
            ]);
            $id_record = $dbo->lastInsertedID();
            flash()->info(tr('Partecipante aggiunto!'));
        }
        break;

    case 'update':
        $nome = filter('nome');
        $email = filter('email');
        if (empty($nome)) {
            flash()->error(tr('Il nome è obbligatorio'));
        } else {
            $dbo->update('totocalcio_partecipanti', [
                'nome' => $nome,
                'email' => $email ?: null,
            ], ['id' => $id_record]);
            flash()->info(tr('Salvataggio completato!'));
        }
        break;

    case 'delete':
        $dbo->delete('totocalcio_partecipanti', ['id' => $id_record]);
        flash()->info(tr('Partecipante eliminato!'));
        break;

    case 'save_colonna':
        $id_partecipante = filter('id_partecipante') ?: $id_record;
        $id_concorso = filter('id_concorso');
        $pronostici = filter('pronostici', null, true);

        if (empty($id_concorso) || empty($pronostici)) {
            flash()->error(tr('Dati incompleti'));
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

        flash()->info(tr('Colonna creata con successo!'));
        break;
}
