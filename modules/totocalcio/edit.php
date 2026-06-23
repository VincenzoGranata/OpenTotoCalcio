<?php

include_once __DIR__.'/../../core.php';

$punti_totali = $dbo->fetchOne('SELECT COALESCE(SUM(punti_totali),0) AS tot FROM totocalcio_colonne WHERE id_partecipante = '.prepare($id_record));
$num_colonne = $dbo->fetchOne('SELECT COUNT(*) AS cnt FROM totocalcio_colonne WHERE id_partecipante = '.prepare($id_record));
$totale_vincite = $dbo->fetchOne('SELECT COALESCE(SUM(importo),0) AS tot FROM totocalcio_vincite WHERE id_partecipante = '.prepare($id_record));
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
        <div class="row text-center">
            <div class="col-md-4">
                <h3 class="text-primary"><?php echo (int)$punti_totali['tot']; ?></h3>
                <small>Punti totali</small>
            </div>
            <div class="col-md-4">
                <h3 class="text-info"><?php echo (int)$num_colonne['cnt']; ?></h3>
                <small>Colonne giocate</small>
            </div>
            <div class="col-md-4">
                <h3 class="text-success"><?php echo number_format((float)$totale_vincite['tot'], 2, ',', '.'); ?>€</h3>
                <small>Vincite totali</small>
            </div>
        </div>
    </div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> Elimina
</a>
