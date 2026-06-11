<?php

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    echo '
    <form method="post" class="d-inline">
        <button type="submit" name="op" value="update_scores" class="btn btn-success">
            <i class="fa fa-refresh"></i> '.tr('Aggiorna Risultati').'
        </button>
    </form>';
}
