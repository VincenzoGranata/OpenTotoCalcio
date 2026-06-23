<?php

include_once __DIR__.'/../../core.php';

use TotoCalcio\CalcoloPuntiService;

switch (filter('op')) {
    case 'update':
        $dbo->update('totocalcio_concorsi', [
            'nome' => filter('nome'),
            'giornata' => filter('giornata'),
            'data_chiusura' => filter('data_chiusura'),
            'stato' => filter('stato'),
        ], ['id' => $id_record]);
        flash()->info(tr('Giornata aggiornata!'));
        redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
        $database->commitTransaction();
        exit;
        break;

    case 'delete':
        $dbo->delete('totocalcio_concorsi', ['id' => $id_record]);
        flash()->info(tr('Giornata eliminata!'));
        redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
        $database->commitTransaction();
        exit;
        break;

    case 'assign_panels':
        if (empty($id_record)) {
            $corrente = $dbo->fetchOne('SELECT id FROM totocalcio_concorsi WHERE stato = \'aperto\' ORDER BY giornata ASC LIMIT 1');
            $id_record = $corrente ? $corrente['id'] : null;
            if (!$id_record) {
                flash()->error(tr('Nessuna giornata aperta trovata'));
                redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
                $database->commitTransaction();
                exit;
            }
        }

        $partite = $dbo->fetchArray('SELECT id FROM totocalcio_partite WHERE id_concorso = '.prepare($id_record).' ORDER BY RAND()');
        $total = count($partite);

        if ($total == 0) {
            flash()->error(tr('Nessuna partita in questa giornata'));
            redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
            $database->commitTransaction();
            exit;
        }

        $dbo->query('UPDATE totocalcio_partite SET pannello = \'obbligatorio\', ordine = 0 WHERE id_concorso = '.prepare($id_record));

        $numObbl = min(7, $total);
        $numOpz = $total - $numObbl;

        foreach ($partite as $i => $p) {
            $pannello = $i < $numObbl ? 'obbligatorio' : 'opzionale';
            $ordine = $i < $numObbl ? ($i + 1) : ($i - $numObbl + 1);
            $dbo->update('totocalcio_partite', [
                'pannello' => $pannello,
                'ordine' => $ordine,
            ], ['id' => $p['id']]);
        }

        $giornata = $dbo->fetchOne('SELECT giornata FROM totocalcio_concorsi WHERE id = '.prepare($id_record));
        flash()->info(tr('Giornata '.($giornata['giornata'] ?? $id_record).': '.$numObbl.' obbligatori, '.$numOpz.' opzionali'));
        redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
        $database->commitTransaction();
        exit;
        break;

    case 'close':
        if (empty($id_record)) {
            $corrente = $dbo->fetchOne('SELECT id FROM totocalcio_concorsi WHERE stato = \'aperto\' ORDER BY giornata ASC LIMIT 1');
            $id_record = $corrente ? $corrente['id'] : null;
            if (!$id_record) {
                flash()->error(tr('Nessuna giornata aperta trovata'));
                redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
                $database->commitTransaction();
                exit;
            }
        }
        $dbo->update('totocalcio_concorsi', ['stato' => 'chiuso'], ['id' => $id_record]);
        flash()->info(tr('Giornata chiusa!'));
        redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
        $database->commitTransaction();
        exit;
        break;
}
