<?php

include_once __DIR__.'/../../core.php';

$miniClassifiche = $dbo->fetchArray('SELECT * FROM totocalcio_mini_classifiche ORDER BY data_inizio DESC');
?>
<button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#modal-nuova-mini">
    <i class="fa fa-plus"></i> <?php echo tr('Nuova Mini Classifica'); ?>
</button>

<!-- Modal Nuova Mini Classifica -->
<div class="modal fade" id="modal-nuova-mini">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h4 class="modal-title"><?php echo tr('Nuova Mini Classifica'); ?></h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?php echo tr('Nome'); ?></label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo tr('Data inizio'); ?></label>
                        <input type="date" name="data_inizio" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo tr('Data fine'); ?> <small class="text-muted">(lascia vuoto se in corso)</small></label>
                        <input type="date" name="data_fine" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo tr('Annulla'); ?></button>
                    <button type="submit" name="op" value="create_mini" class="btn btn-primary">
                        <i class="fa fa-plus"></i> <?php echo tr('Crea'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($miniClassifiche as $mc):
    $premi = $dbo->fetchArray('SELECT * FROM totocalcio_mini_classifiche_premi WHERE id_mini_classifica = '.prepare($mc['id']).' ORDER BY posizione');
    $vincite = $dbo->fetchArray('SELECT v.*, p.nome FROM totocalcio_vincite v JOIN totocalcio_partecipanti p ON p.id = v.id_partecipante WHERE v.id_mini_classifica = '.prepare($mc['id']).' ORDER BY v.posizione');
?>
<div class="modal fade" id="modal-premi-<?php echo $mc['id']; ?>">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fa fa-trophy"></i> <?php echo tr('Premi: ').$mc['nome']; ?></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <h5><?php echo tr('Configura Premi'); ?></h5>
                <form method="post" class="mb-3">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <input type="number" name="posizione" class="form-control" placeholder="Posizione" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <input type="number" name="importo" class="form-control" placeholder="Importo (€)" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <input type="hidden" name="id_mini" value="<?php echo $mc['id']; ?>">
                            <button type="submit" name="op" value="set_premio" class="btn btn-primary">
                                <i class="fa fa-save"></i> <?php echo tr('Salva Premio'); ?>
                            </button>
                        </div>
                    </div>
                </form>

                <?php if (!empty($premi)): ?>
                <table class="table table-bordered table-striped">
                    <thead><tr><th>Posizione</th><th>Importo</th></tr></thead>
                    <tbody>
                        <?php foreach ($premi as $pr): ?>
                        <tr>
                            <td><?php echo $pr['posizione']; ?>°</td>
                            <td><?php echo number_format($pr['importo'], 2, ',', '.'); ?>€</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <hr>
                <h5><?php echo tr('Calcola e Gestisci Vincite'); ?></h5>
                <form method="post" class="d-inline">
                    <input type="hidden" name="id_mini" value="<?php echo $mc['id']; ?>">
                    <button type="submit" name="op" value="calcola_premi" class="btn btn-warning">
                        <i class="fa fa-calculator"></i> <?php echo tr('Calcola Premi'); ?>
                    </button>
                </form>

                <?php if ($mc['stato'] === 'attiva'): ?>
                <form method="post" class="d-inline">
                    <input type="hidden" name="id_mini" value="<?php echo $mc['id']; ?>">
                    <button type="submit" name="op" value="close_mini" class="btn btn-danger">
                        <i class="fa fa-lock"></i> <?php echo tr('Concludi Classifica'); ?>
                    </button>
                </form>
                <?php endif; ?>

                <?php if (!empty($vincite)): ?>
                <hr>
                <table class="table table-bordered table-striped mt-3">
                    <thead><tr><th>#</th><th>Partecipante</th><th>Importo</th><th>Stato</th><th>Azioni</th></tr></thead>
                    <tbody>
                        <?php foreach ($vincite as $v): ?>
                        <tr>
                            <td><?php echo $v['posizione']; ?>°</td>
                            <td><?php echo $v['nome']; ?></td>
                            <td><?php echo number_format($v['importo'], 2, ',', '.'); ?>€</td>
                            <td><?php echo $v['pagato'] ? '<span class="badge badge-success">Pagato</span>' : '<span class="badge badge-warning">In attesa</span>'; ?></td>
                            <td>
                                <?php if (!$v['pagato']): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id_vincita" value="<?php echo $v['id']; ?>">
                                    <button type="submit" name="op" value="mark_paid" class="btn btn-success btn-sm">
                                        <i class="fa fa-check"></i> Paga
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="id_vincita" value="<?php echo $v['id']; ?>">
                                    <button type="submit" name="op" value="mark_unpaid" class="btn btn-secondary btn-sm">
                                        <i class="fa fa-undo"></i> Annulla
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
