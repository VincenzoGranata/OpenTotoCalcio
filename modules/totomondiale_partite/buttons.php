<?php

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    echo '
    <div class="btn-group">
        <a class="btn btn-success" href="'.base_path_osm().'/actions.php?id_module='.$id_module.'&id_record='.$id_record.'&op=update_scores">
            <i class="fa fa-refresh"></i> '.tr('Aggiorna Risultati').'
        </a>
    </div>';
}
