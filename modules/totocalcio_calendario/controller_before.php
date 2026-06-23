<?php

include_once __DIR__.'/../../core.php';

echo '
<form method="post" action="'.base_path_osm().'/actions.php?id_module='.$id_module.'" class="mb-3" style="display:inline-block">
    <button type="submit" name="op" value="sync_all" class="btn btn-primary">
        <i class="fa fa-cloud-download"></i> '.tr('Aggiorna calendario').'
    </button>
</form>';
