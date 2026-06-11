<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/../totomondiale/include/traduzioni.php';

$risultato = ($record['goal_casa'] !== null && $record['goal_ospite'] !== null) ? $record['goal_casa'].' - '.$record['goal_ospite'] : '?';

$pred_count = $dbo->fetchOne('SELECT COUNT(*) AS cnt FROM tot_pronostici WHERE id_partita = '.prepare($id_record));
?>
<form action="" method="post" id="edit-form">
    <input type="hidden" name="op" value="update">
    
    <div class="card card-primary">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <h4><?php echo traduciSquadra($record['squadra_casa']); ?></h4>
                    <?php if ($record['flag_casa']): ?>
                    <img src="https://flagcdn.com/32x24/<?php echo strtolower($record['flag_casa']); ?>.png" class="mb-2">
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-center">
                    <h1 class="display-4"><?php echo $risultato; ?></h1>
                    <span class="badge badge-<?php echo $record['stato'] === 'finished' ? 'success' : ($record['stato'] === 'ongoing' ? 'warning' : 'secondary'); ?>">
                        <?php echo formatStato($record['stato'], $record['minuto']); ?>
                    </span>
                    <br>
                    <small class="text-muted"><?php echo formatoOraItaliana($record['data_partita']); ?></small>
                    <br>
                    <small class="text-muted">Girone: <?php echo $record['girone'] ?: 'N/D'; ?></small>
                </div>
                <div class="col-md-4 text-center">
                    <h4><?php echo traduciSquadra($record['squadra_ospite']); ?></h4>
                    <?php if ($record['flag_ospite']): ?>
                    <img src="https://flagcdn.com/32x24/<?php echo strtolower($record['flag_ospite']); ?>.png" class="mb-2">
                    <?php endif; ?>
                </div>
            </div>
            <hr>
            <div class="row text-center">
                <div class="col-md-6">
                    <strong>Pronostici inseriti:</strong> <?php echo $pred_count['cnt'] ?? 0; ?>
                </div>
                <div class="col-md-6">
                    <?php if ($record['stato'] === 'finished'): ?>
                    <strong>Risultato:</strong> 
                    <?php 
                    $ris = '1';
                    if ($record['goal_casa'] < $record['goal_ospite']) $ris = '2';
                    elseif ($record['goal_casa'] == $record['goal_ospite']) $ris = 'X';
                    ?>
                    <span class="badge badge-success"><?php echo $ris; ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>
