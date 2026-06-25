<?php

include_once __DIR__.'/../../../core.php';

$id_concorso = $id_record;
$concorso = $dbo->fetchOne('SELECT * FROM totocalcio_concorsi WHERE id = '.prepare($id_concorso));
$partite = $dbo->fetchArray('SELECT * FROM totocalcio_partite WHERE id_concorso = '.prepare($id_concorso).' ORDER BY pannello, ordine');

if (empty($partite)) {
    echo '<p>'.tr('Nessuna partita. Carica le partite').'</p>';
    return;
}
?>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Pannello</th>
                <th>Casa</th>
                <th>Risultato</th>
                <th>Ospite</th>
                <th>Data</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($partite as $p):
                $ris = ($p['goal_casa'] !== null && $p['goal_ospite'] !== null) ? $p['goal_casa'].'-'.$p['goal_ospite'] : '?';
                $tipoLabel = match($p['pannello']) {
                    'obbligatorio' => 'OB',
                    'obbligatorio_esatto' => 'ES',
                    'opzionale_scelta' => 'SC',
                    default => $p['pannello'],
                };
                $tipoClass = match($p['pannello']) {
                    'obbligatorio' => 'primary',
                    'obbligatorio_esatto' => 'success',
                    'opzionale_scelta' => 'warning',
                    default => 'secondary',
                };
            ?>
            <tr>
                <td><?php echo $p['ordine']; ?></td>
                <td><span class="badge badge-<?php echo $tipoClass; ?>"><?php echo $tipoLabel; ?></span></td>
                <td>
                    <?php if ($p['logo_casa']): ?><img src="<?php echo $p['logo_casa']; ?>" style="height:16px;margin-right:4px"><?php endif; ?>
                    <?php echo $p['squadra_casa']; ?>
                </td>
                <td class="text-center"><strong><?php echo $ris; ?></strong></td>
                <td>
                    <?php if ($p['logo_ospite']): ?><img src="<?php echo $p['logo_ospite']; ?>" style="height:16px;margin-right:4px"><?php endif; ?>
                    <?php echo $p['squadra_ospite']; ?>
                </td>
                <td><?php echo $p['data_partita'] ? date('d/m/Y H:i', strtotime($p['data_partita'])) : '-'; ?></td>
                <td><?php echo $p['stato']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
