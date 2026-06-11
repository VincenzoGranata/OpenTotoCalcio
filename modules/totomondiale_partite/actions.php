<?php

include_once __DIR__.'/../../core.php';

use TotoMondiale\ApiFootballService;

switch (filter('op')) {
    case 'sync':
        try {
            $service = new ApiFootballService();
            $count = $service->syncMatches();
            flash()->info(tr('Sincronizzazione completata! Partite caricate/aggiornate: '.$count));
        } catch (\Exception $e) {
            flash()->error(tr('Errore sincronizzazione: '.$e->getMessage()));
        }
        break;

    case 'update_scores':
        try {
            $service = new ApiFootballService();
            $updated = $service->updateLiveScores();
            $calculated = $service->calculateAllPoints();
            flash()->info(tr('Risultati aggiornati: '.$updated.' partite. Punti ricalcolati per '.$calculated.' pronostici.'));
        } catch (\Exception $e) {
            flash()->error(tr('Errore aggiornamento: '.$e->getMessage()));
        }
        break;

    case 'calcola_bonus':
        try {
            $vincitore = filter('vincitore');
            $capocannoniere = filter('capocannoniere');

            $service = new ApiFootballService();
            $count = $service->calculateBonusPoints($vincitore, $capocannoniere);

            $msg = '';
            if (!empty($vincitore)) {
                $msg .= 'Vincitore: '.$vincitore.'. ';
            }
            if (!empty($capocannoniere)) {
                $msg .= 'Capocannoniere: '.$capocannoniere.'. ';
            }
            $msg .= 'Bonus aggiornati per '.$count.' pronostici.';

            flash()->info(tr($msg));
        } catch (\Exception $e) {
            flash()->error(tr('Errore calcolo bonus: '.$e->getMessage()));
        }
        break;

    case 'update':
        flash()->info(tr('Le partite sono gestite tramite sincronizzazione automatica.'));
        break;

    case 'delete':
        flash()->error(tr('Non puoi eliminare una partita direttamente.'));
        break;
}
