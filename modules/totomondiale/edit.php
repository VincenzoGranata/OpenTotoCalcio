<?php

include_once __DIR__.'/../../core.php';
?>
<form action="" method="post" id="edit-form">
    <input type="hidden" name="op" value="update">
    <div class="card card-primary">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "Nome", "name": "nome", "required": 1, "value": "$nome$" ]}
                </div>
                <div class="col-md-6">
                    {[ "type": "text", "label": "Email", "name": "email", "value": "$email$" ]}
                </div>
            </div>
        </div>
    </div>
</form>

<div class="card card-primary">
    <div class="card-body">
        <h4>Riepilogo Punteggi</h4>
        <div class="row">
            <?php
            $punti_partite = $dbo->fetchOne('SELECT COALESCE(SUM(punti),0) AS tot FROM tot_pronostici WHERE id_partecipante = '.prepare($id_record));
            $punti_bonus = $dbo->fetchOne('SELECT COALESCE(SUM(punti),0) AS tot FROM tot_bonus WHERE id_partecipante = '.prepare($id_record));
            $totale = ($punti_partite['tot'] ?? 0) + ($punti_bonus['tot'] ?? 0);
            ?>
            <div class="col-md-4 text-center">
                <h3 class="text-primary"><?php echo $punti_partite['tot'] ?? 0; ?></h3>
                <small>Pronostici partite</small>
            </div>
            <div class="col-md-4 text-center">
                <h3 class="text-success"><?php echo $punti_bonus['tot'] ?? 0; ?></h3>
                <small>Bonus</small>
            </div>
            <div class="col-md-4 text-center">
                <h3 class="text-warning"><?php echo $totale; ?></h3>
                <small>Totale</small>
            </div>
        </div>
    </div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> Elimina
</a>
