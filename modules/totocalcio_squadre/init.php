<?php

include_once __DIR__.'/../../core.php';

if (!empty($id_record)) {
    $record = $dbo->fetchOne('SELECT * FROM totocalcio_squadre WHERE id = '.prepare($id_record));
}
