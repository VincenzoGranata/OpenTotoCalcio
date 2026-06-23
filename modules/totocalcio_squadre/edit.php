<?php

include_once __DIR__.'/../../core.php';

$giocatori = $dbo->fetchArray('SELECT * FROM totocalcio_giocatori WHERE id_squadra = '.prepare($id_record).' ORDER BY nome ASC');
?>
<form action="" method="post" id="edit-form">
    <input type="hidden" name="op" value="update">
    <div class="card card-primary">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 text-center">
                    <?php if ($record['logo']): ?>
                        <img src="<?php echo $record['logo']; ?>" style="max-height:64px;max-width:64px" alt="Logo">
                    <?php else: ?>
                        <div class="text-muted" style="height:64px;line-height:64px"><i class="fa fa-shield fa-2x"></i></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-5">
                    {[ "type": "text", "label": "Nome squadra", "name": "nome", "required": 1, "value": "$nome$" ]}
                </div>
                <div class="col-md-5">
                    {[ "type": "text", "label": "URL logo", "name": "logo", "value": "$logo$" ]}
                </div>
            </div>
        </div>
    </div>
</form>

<div class="card card-primary">
    <div class="card-body">
        <h4>Rosa giocatori (<?php echo count($giocatori); ?>)</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nome Giocatore</th>
                        <th>Ruolo</th>
                        <th>API ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($giocatori as $g): ?>
                    <tr>
                        <td><?php echo $g['nome']; ?></td>
                        <td><?php echo $g['ruolo'] ?: '-'; ?></td>
                        <td><?php echo $g['id_api'] ?: '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($giocatori)): ?>
                    <tr><td colspan="3" class="text-center text-muted">Nessun giocatore. Sincronizza da API.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Form aggiungi giocatore manuale -->
<div class="card card-info mt-3">
    <div class="card-header"><h4>Aggiungi giocatore manualmente</h4></div>
    <div class="card-body">
        <form method="post" class="form-inline">
            <input type="hidden" name="op" value="add_player">
            <div class="form-group mr-2">
                <input type="text" name="nome_giocatore" class="form-control" placeholder="Nome giocatore" required style="width:250px">
            </div>
            <div class="form-group mr-2">
                <select name="ruolo_giocatore" class="form-control">
                    <option value="">Ruolo...</option>
                    <option value="Portiere">Portiere</option>
                    <option value="Difensore">Difensore</option>
                    <option value="Centrocampista">Centrocampista</option>
                    <option value="Attaccante">Attaccante</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">
                <i class="fa fa-plus"></i> Aggiungi
            </button>
        </form>
    </div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> Elimina
</a>
