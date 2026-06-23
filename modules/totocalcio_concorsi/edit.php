<?php

include_once __DIR__.'/../../core.php';

$partite = $dbo->fetchArray('SELECT * FROM totocalcio_partite WHERE id_concorso = '.prepare($id_record).' ORDER BY pannello, ordine');
$numColonne = $dbo->fetchOne('SELECT COUNT(*) AS cnt FROM totocalcio_colonne WHERE id_concorso = '.prepare($id_record));
$numObbl = count(array_filter($partite, fn($p) => $p['pannello'] === 'obbligatorio'));
$numOpz = count(array_filter($partite, fn($p) => $p['pannello'] === 'opzionale'));
?>
<form action="" method="post" id="edit-form">
    <input type="hidden" name="op" value="update">
    <div class="card card-primary">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    {[ "type": "text", "label": "Nome giornata", "name": "nome", "required": 1, "value": "$nome$" ]}
                </div>
                <div class="col-md-2">
                    {[ "type": "number", "label": "Giornata", "name": "giornata", "required": 1, "value": "$giornata$" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "datetime", "label": "Chiusura pronostici", "name": "data_chiusura", "required": 1, "value": "$data_chiusura$" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "select", "label": "Stato", "name": "stato", "value": "$stato$", "values": [{"id": "aperto", "text": "Aperto"}, {"id": "chiuso", "text": "Chiuso"}, {"id": "concluso", "text": "Concluso"}] ]}
                </div>
            </div>
        </div>
    </div>
</form>

<div class="card card-primary">
    <div class="card-body">
        <h4>Riepilogo</h4>
        <div class="row text-center">
            <div class="col-md-4">
                <h3 class="text-primary"><?php echo $numObbl; ?></h3>
                <small>Obbligatorie</small>
            </div>
            <div class="col-md-4">
                <h3 class="text-warning"><?php echo $numOpz; ?></h3>
                <small>Opzionali</small>
            </div>
            <div class="col-md-4">
                <h3 class="text-success"><?php echo $numColonne['cnt'] ?? 0; ?></h3>
                <small>Colonne inserite</small>
            </div>
        </div>
    </div>
</div>
