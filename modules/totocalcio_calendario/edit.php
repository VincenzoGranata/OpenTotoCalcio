<?php

include_once __DIR__.'/../../core.php';

if (empty($id_record)) {
    return;
}

$record = $dbo->fetchOne('SELECT * FROM totocalcio_concorsi WHERE id = '.prepare($id_record));
if (!$record) {
    echo '<p>'.tr('Giornata non trovata.').'</p>';
    return;
}

$partite = $dbo->fetchArray('SELECT COUNT(*) AS cnt FROM totocalcio_partite WHERE id_concorso = '.prepare($id_record));
$numPartite = $partite[0]['cnt'] ?? 0;
?>
<div class="card card-primary">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 text-center">
                <h1 class="text-primary"><?php echo $record['giornata']; ?></h1>
                <small>Giornata</small>
            </div>
            <div class="col-md-3 text-center">
                <h3><?php echo $numPartite; ?></h3>
                <small>Partite</small>
            </div>
            <div class="col-md-3 text-center">
                <h3>
                    <?php
                    $badge = '';
                    if ($record['stato'] === 'aperto') $badge = '<span class="badge bg-success" style="font-size:1rem">Aperto</span>';
                    elseif ($record['stato'] === 'chiuso') $badge = '<span class="badge bg-warning text-dark" style="font-size:1rem">Chiuso</span>';
                    elseif ($record['stato'] === 'concluso') $badge = '<span class="badge bg-secondary" style="font-size:1rem">Concluso</span>';
                    echo $badge;
                    ?>
                </h3>
                <small>Stato</small>
            </div>
            <div class="col-md-3 text-center">
                <h3><?php echo $record['data_chiusura'] ? date('d/m/Y H:i', strtotime($record['data_chiusura'])) : '-'; ?></h3>
                <small>Chiusura pronostici</small>
            </div>
        </div>
    </div>
</div>
