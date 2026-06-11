<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/../totomondiale/include/traduzioni.php';

echo '
<form method="post" class="mb-3">
    <div class="btn-group">
        <button type="submit" name="op" value="sync" class="btn btn-primary">
            <i class="fa fa-cloud-download"></i> '.tr('Carica Partite (worldcup26.ir)').'
        </button>
        <button type="submit" name="op" value="update_scores" class="btn btn-success">
            <i class="fa fa-refresh"></i> '.tr('Aggiorna Risultati e Punteggi').'
        </button>
        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modal-bonus">
            <i class="fa fa-trophy"></i> '.tr('Calcola Bonus Finali').'
        </button>
    </div>
</form>

<div class="modal fade" id="modal-bonus">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="'.base_path_osm().'/actions.php?id_module='.$id_module.'">
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fa fa-trophy"></i> '.tr('Calcola Bonus Finali').'</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>'.tr('Vincitore del Mondiale').'</label>
                        <select name="vincitore" class="form-control select2">
                            <option value="">'.tr('Seleziona...').'</option>';
$squadre = $dbo->fetchArray('SELECT DISTINCT squadra_casa AS nome FROM tot_partite UNION SELECT DISTINCT squadra_ospite FROM tot_partite ORDER BY nome');
foreach ($squadre as $s) {
    echo '
                            <option value="'.prepare($s['nome']).'">'.traduciSquadra($s['nome']).'</option>';
}
echo '
                        </select>
                    </div>
                    <div class="form-group">
                        <label>'.tr('Capocannoniere').'</label>
                        <input type="text" name="capocannoniere" class="form-control" placeholder="'.tr('Nome giocatore').'">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">'.tr('Annulla').'</button>
                    <button type="submit" name="op" value="calcola_bonus" class="btn btn-warning">
                        <i class="fa fa-calculator"></i> '.tr('Calcola Punti Bonus').'
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>';
