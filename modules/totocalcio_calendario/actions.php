<?php

include_once __DIR__.'/../../core.php';

use TotoCalcio\FotmobService;

switch (filter('op')) {
    case 'sync_all':
        try {
            $service = new FotmobService();
            $total = $service->syncAll();
            flash()->info(tr('Calendario 2026/27 importato: '.$total.' nuove partite'));
        } catch (\Exception $e) {
            flash()->error(tr('Errore: '.$e->getMessage()));
        }
        redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
        $database->commitTransaction();
        exit;
        break;

    case 'dettaglio_partita':
        $id_partita = $id_record;
        $p = $dbo->fetchOne('SELECT * FROM totocalcio_partite WHERE id = '.prepare($id_partita));
        if (!$p) {
            echo '<div class="alert alert-danger">Partita non trovata</div>';
            break;
        }

        $logoCasa = $p['logo_casa'] ? '<img src="'.$p['logo_casa'].'" style="height:48px">' : '<i class="fa fa-shield fa-3x text-muted"></i>';
        $logoOspite = $p['logo_ospite'] ? '<img src="'.$p['logo_ospite'].'" style="height:48px">' : '<i class="fa fa-shield fa-3x text-muted"></i>';
        $ris = ($p['goal_casa'] !== null && $p['goal_ospite'] !== null) ? $p['goal_casa'].' - '.$p['goal_ospite'] : '? - ?';
        $data = $p['data_partita'] ? date('d/m/Y H:i', strtotime($p['data_partita'])) : '-';

        $statoLabel = '';
        if ($p['stato'] === 'scheduled') $statoLabel = '<span class="badge bg-info" style="font-size:1.2rem">Programmata</span>';
        elseif ($p['stato'] === 'ongoing') $statoLabel = '<span class="badge bg-warning text-dark" style="font-size:1.2rem">'.$p['minuto'].'\'</span>';
        elseif ($p['stato'] === 'finished') $statoLabel = '<span class="badge bg-success" style="font-size:1.2rem">Terminata</span>';
        else $statoLabel = '<span class="badge bg-secondary" style="font-size:1.2rem">'.$p['stato'].'</span>';

        echo '
        <div class="row text-center">
            <div class="col-5">
                <div class="mb-2">'.$logoCasa.'</div>
                <h4>'.$p['squadra_casa'].'</h4>
            </div>
            <div class="col-2">
                <div style="font-size:2.5rem;font-weight:700;margin-top:20px">'.$ris.'</div>
            </div>
            <div class="col-5">
                <div class="mb-2">'.$logoOspite.'</div>
                <h4>'.$p['squadra_ospite'].'</h4>
            </div>
        </div>
        <hr>
        <div class="row text-center">
            <div class="col-4">
                <small class="text-muted">Data</small><br>
                <strong>'.$data.'</strong>
            </div>
            <div class="col-4">
                <small class="text-muted">Stato</small><br>
                '.$statoLabel.'
            </div>
            <div class="col-4">
                <small class="text-muted">Giornata</small><br>
                <strong>'.$p['id_concorso'].'</strong>
            </div>
        </div>';
        break;
}
