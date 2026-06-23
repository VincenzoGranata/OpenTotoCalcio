<?php

include_once __DIR__.'/../../../core.php';

if (!empty($is_title_request)) {
    echo tr('Classifica Totocalcio');
    return;
}

$classifica = database()->fetchArray('
    SELECT p.id, p.nome,
        COALESCE(SUM(c.punti_totali), 0) AS totale
    FROM totocalcio_partecipanti p
    LEFT JOIN totocalcio_colonne c ON c.id_partecipante = p.id
    GROUP BY p.id, p.nome
    ORDER BY totale DESC
    LIMIT 20
');

if (empty($classifica)) {
    echo '<p>'.tr('Nessun partecipante').'</p>';
    return;
}
?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th class="text-center" style="width:40px">#</th>
                <th><?php echo tr('Nome'); ?></th>
                <th class="text-center"><?php echo tr('Punti'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php $pos = 1; foreach ($classifica as $p): ?>
            <tr>
                <td class="text-center">
                    <?php if ($pos === 1): ?>
                        <span class="badge" style="background-color: gold; color: #333;">1</span>
                    <?php elseif ($pos === 2): ?>
                        <span class="badge" style="background-color: silver; color: #333;">2</span>
                    <?php elseif ($pos === 3): ?>
                        <span class="badge" style="background-color: #cd7f32; color: #fff;">3</span>
                    <?php else: ?>
                        <span class="badge badge-secondary"><?php echo $pos; ?></span>
                    <?php endif; ?>
                </td>
                <td><strong><?php echo $p['nome']; ?></strong></td>
                <td class="text-center"><strong><?php echo (int)$p['totale']; ?></strong></td>
            </tr>
            <?php ++$pos; endforeach; ?>
        </tbody>
    </table>
</div>
