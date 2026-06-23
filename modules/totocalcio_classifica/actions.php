<?php

include_once __DIR__.'/../../core.php';

use TotoCalcio\CalcoloPuntiService;

switch (filter('op')) {
    case 'create_mini':
        $nome = filter('nome');
        $data_inizio = filter('data_inizio');
        $data_fine = filter('data_fine') ?: null;

        if (empty($nome) || empty($data_inizio)) {
            flash()->error(tr('Nome e data inizio obbligatori'));
            break;
        }

        $dbo->insert('totocalcio_mini_classifiche', [
            'nome' => $nome,
            'data_inizio' => $data_inizio,
            'data_fine' => $data_fine,
            'stato' => 'attiva',
        ]);
        flash()->info(tr('Mini classifica creata!'));
        break;

    case 'close_mini':
        $id = filter('id_mini');
        if (empty($id)) break;

        $dbo->update('totocalcio_mini_classifiche', ['stato' => 'conclusa'], ['id' => $id]);
        flash()->info(tr('Mini classifica conclusa!'));
        break;

    case 'calcola_premi':
        $id = filter('id_mini');
        if (empty($id)) break;

        $service = new CalcoloPuntiService();
        $vincitori = $service->determinaVincitoriMiniClassifica($id);

        // Elimina vincite precedenti
        $dbo->query('DELETE FROM totocalcio_vincite WHERE id_mini_classifica = '.prepare($id));

        foreach ($vincitori as $v) {
            $dbo->insert('totocalcio_vincite', [
                'id_partecipante' => $v['id_partecipante'],
                'id_mini_classifica' => $id,
                'posizione' => $v['posizione'],
                'importo' => $v['importo'],
            ]);
        }

        flash()->info(tr('Premi calcolati per '.count($vincitori).' vincitori!'));
        break;

    case 'set_premio':
        $id_mini = filter('id_mini');
        $posizione = filter('posizione');
        $importo = filter('importo');

        if (empty($id_mini) || empty($posizione)) break;

        $existing = $dbo->fetchOne('SELECT id FROM totocalcio_mini_classifiche_premi WHERE id_mini_classifica = '.prepare($id_mini).' AND posizione = '.prepare($posizione));
        if ($existing) {
            $dbo->update('totocalcio_mini_classifiche_premi', ['importo' => $importo], ['id' => $existing['id']]);
        } else {
            $dbo->insert('totocalcio_mini_classifiche_premi', [
                'id_mini_classifica' => $id_mini,
                'posizione' => $posizione,
                'importo' => $importo ?: 0,
            ]);
        }
        flash()->info(tr('Premio salvato!'));
        break;

    case 'mark_paid':
        $id_vincita = filter('id_vincita');
        if (empty($id_vincita)) break;
        $dbo->update('totocalcio_vincite', [
            'pagato' => 1,
            'data_pagamento' => date('Y-m-d H:i:s'),
        ], ['id' => $id_vincita]);
        flash()->info(tr('Pagamento registrato!'));
        break;

    case 'mark_unpaid':
        $id_vincita = filter('id_vincita');
        if (empty($id_vincita)) break;
        $dbo->update('totocalcio_vincite', [
            'pagato' => 0,
            'data_pagamento' => null,
        ], ['id' => $id_vincita]);
        flash()->info(tr('Pagamento annullato!'));
        break;
}
