<?php

include_once __DIR__.'/../../../core.php';

$id_partita = $id_record;
$partita = $dbo->fetchOne('SELECT * FROM tot_partite WHERE id = '.prepare($id_partita));
$pronostici = $dbo->fetchArray('
    SELECT pr.*, p.nome AS partecipante
    FROM tot_pronostici pr
    JOIN tot_partecipanti p ON p.id = pr.id_partecipante
    WHERE pr.id_partita = '.prepare($id_partita).'
    ORDER BY p.nome ASC
');

if (empty($pronostici)) {
    echo '<p>'.tr('Nessun pronostico inserito per questa partita.').'</p>';
    return;
}
?>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th><?php echo tr('Partecipante'); ?></th>
                <th class="text-center" style="width:80px"><?php echo tr('Pronostico'); ?></th>
                <?php if ($partita['stato'] === 'finished'): ?>
                <th class="text-center" style="width:80px"><?php echo tr('Punti'); ?></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pronostici as $pr): ?>
            <tr>
                <td><?php echo $pr['partecipante']; ?></td>
                <td class="text-center">
                    <span class="badge badge-<?php 
                        echo $pr['pronostico'] === '1' ? 'primary' : ($pr['pronostico'] === 'X' ? 'warning' : 'danger'); 
                    ?>"><?php echo $pr['pronostico']; ?></span>
                </td>
                <?php if ($partita['stato'] === 'finished'): ?>
                <td class="text-center">
                    <span class="badge badge-<?php echo $pr['punti'] > 0 ? 'success' : 'secondary'; ?>">
                        <?php echo $pr['punti'] ?: '0'; ?>
                    </span>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
