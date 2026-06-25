<?php

include_once __DIR__.'/../../core.php';

$concorso = $dbo->fetchOne('SELECT * FROM totocalcio_concorsi WHERE id = '.prepare($id_record));
$partite = $dbo->fetchArray('SELECT * FROM totocalcio_partite WHERE id_concorso = '.prepare($id_record).' ORDER BY pannello, ordine');
$numColonne = $dbo->fetchOne('SELECT COUNT(*) AS cnt FROM totocalcio_colonne WHERE id_concorso = '.prepare($id_record));
$numObbl = count(array_filter($partite, fn($p) => $p['pannello'] === 'obbligatorio'));
$numEsatto = count(array_filter($partite, fn($p) => $p['esatto'] == 1));
$numScelta = count(array_filter($partite, fn($p) => $p['pannello'] === 'opzionale_scelta'));
$isAperto = $concorso && $concorso['stato'] === 'aperto';
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
            <div class="col-md-3">
                <h3 class="text-primary"><?php echo (int)$numObbl; ?></h3>
                <small>Obbligatorie</small>
            </div>
            <div class="col-md-3">
                <h3 class="text-success"><?php echo (int)$numEsatto; ?></h3>
                <small>Risultato Esatto</small>
            </div>
            <div class="col-md-3">
                <h3 class="text-warning"><?php echo (int)$numScelta; ?></h3>
                <small>Scelta</small>
            </div>
            <div class="col-md-3">
                <h3 class="text-info"><?php echo (int)$numColonne['cnt']; ?></h3>
                <small>Colonne inserite</small>
            </div>
        </div>
    </div>
</div>

<?php if ($isAperto): ?>
<div class="btn-group mb-3">
    <form method="post" action="<?php echo base_path_osm(); ?>/actions.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>" style="display:inline-block">
        <button type="submit" name="op" value="assign_panels" class="btn btn-info" <?php echo $numColonne['cnt'] > 0 ? 'disabled' : ''; ?>>
            <i class="fa fa-random"></i> Assegna Pannelli
        </button>
    </form>
    <form method="post" action="<?php echo base_path_osm(); ?>/actions.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>" style="display:inline-block;margin-left:5px">
        <button type="submit" name="op" value="close" class="btn btn-warning">
            <i class="fa fa-lock"></i> Chiudi Concorso
        </button>
    </form>
</div>
<?php endif; ?>
