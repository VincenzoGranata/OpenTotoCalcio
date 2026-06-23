<?php

include_once __DIR__.'/../../core.php';

echo '
<form method="post" action="'.base_path_osm().'/actions.php?id_module='.$id_module.'" class="mb-3" style="display:inline-block">
    <div class="btn-group">
        <button type="submit" name="op" value="sync_teams" class="btn btn-success">
            <i class="fa fa-cloud-download-alt"></i> '.tr('Sincronizza squadre + loghi').'
        </button>
        <button type="submit" name="op" value="sync_all_players" class="btn btn-warning">
            <i class="fa fa-users"></i> '.tr('Sincronizza rose giocatori').'
        </button>
    </div>
</form>';

if (!empty($id_record)) {
    echo '
    <form method="post" action="'.base_path_osm().'/actions.php?id_module='.$id_module.'&id_record='.$id_record.'" class="d-inline">
        <button type="submit" name="op" value="sync_players" class="btn btn-info mb-3">
            <i class="fa fa-refresh"></i> '.tr('Sincronizza rosa').'
        </button>
    </form>';
}
