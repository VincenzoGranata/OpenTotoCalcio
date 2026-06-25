<?php

include_once __DIR__.'/../../../core.php';

$id_partecipante = $id_partecipante ?? $id_record ?? 0;
if (empty($id_partecipante)) { echo '<div class="alert alert-warning">'.tr('Partecipante non trovato').'</div>'; return; }

$vincite = $dbo->fetchArray('
    SELECT v.*, mc.nome AS mini_classifica
    FROM totocalcio_vincite v
    LEFT JOIN totocalcio_mini_classifiche mc ON mc.id = v.id_mini_classifica
    WHERE v.id_partecipante = '.prepare($id_partecipante).'
    ORDER BY v.created_at DESC
');

$totale = $dbo->fetchOne('SELECT COALESCE(SUM(importo),0) AS tot FROM totocalcio_vincite WHERE id_partecipante = '.prepare($id_partecipante));
?>
<div class="card card-primary">
    <div class="card-body">
        <h4>Riepilogo Vincite</h4>
        <div class="row text-center">
            <div class="col-md-6">
                <h3 class="text-success"><?php echo number_format((float)$totale['tot'], 2, ',', '.'); ?>€</h3>
                <small>Totale vinto</small>
            </div>
            <div class="col-md-6">
                <h3 class="text-info"><?php echo count($vincite); ?></h3>
                <small>Premi vinti</small>
            </div>
        </div>
    </div>
</div>

<?php if (empty($vincite)): ?>
<p class="text-muted">Nessuna vincita registrata.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Mini Classifica</th>
                <th class="text-center">Posizione</th>
                <th class="text-center">Importo</th>
                <th class="text-center">Stato</th>
                <th>Data pagamento</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vincite as $v): ?>
            <tr>
                <td><?php echo $v['mini_classifica'] ?: 'Finale'; ?></td>
                <td class="text-center"><?php echo $v['posizione']; ?>°</td>
                <td class="text-center"><strong><?php echo number_format($v['importo'], 2, ',', '.'); ?>€</strong></td>
                <td class="text-center">
                    <?php if ($v['pagato']): ?><span class="badge badge-success">Pagato</span>
                    <?php else: ?><span class="badge badge-warning">In attesa</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $v['data_pagamento'] ? date('d/m/Y', strtotime($v['data_pagamento'])) : '-'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
