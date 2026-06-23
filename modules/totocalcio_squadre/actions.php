<?php

include_once __DIR__.'/../../core.php';

use TotoCalcio\ApiFootballService;

switch (filter('op')) {
    case 'update':
        $nome = filter('nome');
        $logo = filter('logo');
        if (empty($nome)) {
            flash()->error(tr('Il nome è obbligatorio'));
        } else {
            $dbo->update('totocalcio_squadre', [
                'nome' => $nome,
                'logo' => $logo ?: null,
            ], ['id' => $id_record]);
            flash()->info(tr('Squadra aggiornata!'));
        }
        break;

    case 'add_player':
        $nome = filter('nome_giocatore');
        $ruolo = filter('ruolo_giocatore');
        if (!empty($nome)) {
            $dbo->insert('totocalcio_giocatori', [
                'nome' => $nome,
                'id_squadra' => $id_record,
                'ruolo' => $ruolo ?: null,
            ]);
            flash()->info(tr('Giocatore aggiunto!'));
        }
        break;

    case 'sync_teams':
        try {
            $service = new ApiFootballService();
            $count = $service->syncTeams();
            flash()->info(tr('Squadre sincronizzate con loghi: '.$count));
        } catch (\Exception $e) {
            flash()->error(tr('Errore: '.$e->getMessage()));
        }
        redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
        $database->commitTransaction();
        exit;

    case 'sync_players':
        try {
            $service = new ApiFootballService();
            $count = $service->syncPlayers($id_record);
            flash()->info(tr('Giocatori sincronizzati: '.$count));
        } catch (\Exception $e) {
            flash()->error(tr('Errore: '.$e->getMessage()));
        }
        redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
        $database->commitTransaction();
        exit;

    case 'sync_all_players':
        try {
            $service = new ApiFootballService();
            $teamCount = $service->syncTeams();
            $playerCount = $service->syncAllPlayers();
            flash()->info(tr('Squadre: '.$teamCount.', Giocatori: '.$playerCount));
        } catch (\Exception $e) {
            flash()->error(tr('Errore: '.$e->getMessage()));
        }
        redirect_url(base_path_osm().'/controller.php?id_module='.$id_module);
        $database->commitTransaction();
        exit;
}
