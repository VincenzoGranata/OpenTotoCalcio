<?php

error_log("[concorsi_file] FILE LOADED at " . __FILE__);

include_once __DIR__.'/../../core.php';

use TotoCalcio\CalcoloPuntiService;

try {
    error_log("[concorsi_actions] START - op=" . (filter('op') ?: 'NULL') . " id_record=" . ($id_record ?? 'NULL'));

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
        error_log("[assign_panels] Started - id_record: " . ($id_record ?? 'NULL') . " id_module: " . ($id_module ?? 'NULL'));

        if (empty($id_record)) {
            $corrente = $dbo->fetchOne('SELECT id FROM totocalcio_concorsi WHERE stato = \'aperto\' ORDER BY giornata ASC LIMIT 1');
            $id_record = $corrente ? $corrente['id'] : null;
            if (!$id_record) {
                error_log("[assign_panels] Nessuna giornata aperta");
                flash()->error(tr('Nessuna giornata aperta trovata'));
                redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
                $database->commitTransaction();
                exit;
            }
        }

        $partite = $dbo->fetchArray('SELECT id FROM totocalcio_partite WHERE id_concorso = '.prepare($id_record).' ORDER BY RAND()');
        $total = count($partite);
        error_log("[assign_panels] Partite trovate: " . $total);

        if ($total == 0) {
            error_log("[assign_panels] Nessuna partita");
            flash()->error(tr('Nessuna partita in questa giornata'));
            redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
            $database->commitTransaction();
            exit;
        }

        $dbo->query('UPDATE totocalcio_partite SET pannello = \'obbligatorio\', ordine = 0, esatto = 0 WHERE id_concorso = '.prepare($id_record));
        error_log("[assign_panels] Reset completato");

        $numObbl = min(7, $total);
        $numOpz = $total - $numObbl;

        foreach ($partite as $i => $p) {
            $pannello = $i < $numObbl ? 'obbligatorio' : 'opzionale_scelta';
            $ordine = $i < $numObbl ? ($i + 1) : ($i - $numObbl + 1);
            $dbo->update('totocalcio_partite', [
                'pannello' => $pannello,
                'ordine' => $ordine,
            ], ['id' => $p['id']]);
        }
        error_log("[assign_panels] Pannelli assegnati");

        // Tra tutte le 10 partite: 1 diventa "esatto" (3pt extra)
        $tutte = $dbo->fetchArray('SELECT id FROM totocalcio_partite WHERE id_concorso = '.prepare($id_record).' ORDER BY RAND() LIMIT 1');
        if (!empty($tutte)) {
            $dbo->update('totocalcio_partite', ['esatto' => 1], ['id' => $tutte[0]['id']]);
            error_log("[assign_panels] Esatto assegnato a: " . $tutte[0]['id']);
        }

        $giornata = $dbo->fetchOne('SELECT giornata FROM totocalcio_concorsi WHERE id = '.prepare($id_record));
        flash()->info(tr('Giornata '.($giornata['giornata'] ?? $id_record).': 7 obbligatori, 3 scelta, 1 risultato esatto (max 11pt)'));
        error_log("[assign_panels] Completato con successo. Redirect a: controller.php?id_module=" . $id_module);
        $redirect_url = base_path_osm().'/controller.php?id_module='.$id_module;
        error_log("[assign_panels] URL completa: " . $redirect_url);
        redirect_url($redirect_url);
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
} catch (Throwable $e) {
    error_log("[concorsi_actions] EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    error_log("[concorsi_actions] Trace: " . $e->getTraceAsString());
    $_SESSION['login_error'] = 'Errore: ' . $e->getMessage();
    header('Location: /index.php');
    exit;
}
