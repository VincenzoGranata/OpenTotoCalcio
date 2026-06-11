<?php

switch (filter('op')) {
    case 'add':
        $nome = filter('nome_add');
        $email = filter('email_add');

        if (empty($nome)) {
            flash()->error(tr('Il nome è obbligatorio'));
        } else {
            $dbo->insert('tot_partecipanti', [
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
            $dbo->update('tot_partecipanti', [
                'nome' => $nome,
                'email' => $email ?: null,
            ], ['id' => $id_record]);
            flash()->info(tr('Salvataggio completato!'));
        }
        break;

    case 'delete':
        $dbo->delete('tot_partecipanti', ['id' => $id_record]);
        flash()->info(tr('Partecipante eliminato!'));
        break;

    case 'save_pronostico':
        $id_partecipante = filter('id_partecipante') ?: $id_record;
        $id_partita = filter('id_partita');
        $pronostico = filter('pronostico');

        $existing = $dbo->fetchOne('SELECT id FROM tot_pronostici WHERE id_partecipante = '.prepare($id_partecipante).' AND id_partita = '.prepare($id_partita));
        
        if ($existing) {
            $dbo->update('tot_pronostici', ['pronostico' => $pronostico], ['id' => $existing['id']]);
        } else {
            $dbo->insert('tot_pronostici', [
                'id_partecipante' => $id_partecipante,
                'id_partita' => $id_partita,
                'pronostico' => $pronostico,
            ]);
        }

        if (!empty($_POST['ajax'])) {
            while (ob_get_level()) {
                ob_end_clean();
            }
            $dbo->query('COMMIT');
            echo json_encode(['success' => true, 'message' => tr('Pronostico salvato!')]);
            exit;
        }
        flash()->info(tr('Pronostico salvato!'));
        break;

    case 'save_bonus':
        $id_partecipante = filter('id_partecipante');
        $tipo = filter('tipo');
        $valore = filter('valore');

        $existing = $dbo->fetchOne('SELECT id FROM tot_bonus WHERE id_partecipante = '.prepare($id_partecipante).' AND tipo = '.prepare($tipo));

        if ($existing) {
            $dbo->update('tot_bonus', ['valore' => $valore], ['id' => $existing['id']]);
        } else {
            $dbo->insert('tot_bonus', [
                'id_partecipante' => $id_partecipante,
                'tipo' => $tipo,
                'valore' => $valore,
            ]);
        }

        flash()->info(tr('Bonus salvato!'));
        break;
}
