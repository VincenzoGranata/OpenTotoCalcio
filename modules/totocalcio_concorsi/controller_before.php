<?php

include_once __DIR__.'/../../core.php';

echo '
<form method="post" action="'.base_path_osm().'/actions.php?id_module='.$id_module.'" class="mb-3" style="display:inline-block">
    <div class="btn-group">
        <button type="submit" name="op" value="assign_panels" class="btn btn-info">
            <i class="fa fa-random"></i> '.tr('Assegna Pannelli').'
        </button>
        <button type="submit" name="op" value="close" class="btn btn-warning">
            <i class="fa fa-lock"></i> '.tr('Chiudi Concorso').'
        </button>
    </div>
</form>';
