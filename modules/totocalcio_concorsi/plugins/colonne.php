<?php

include_once __DIR__.'/../../../core.php';

$id_concorso = $id_record;

$colonne = $dbo->fetchArray('
    SELECT c.*, p.nome AS partecipante
    FROM totocalcio_colonne c
    JOIN totocalcio_partecipanti p ON p.id = c.id_partecipante
    WHERE c.id_concorso = '.prepare($id_concorso).'
    ORDER BY c.punti_totali DESC, p.nome ASC
');

if (empty($colonne)) {
    echo '<p>'.tr('Nessuna colonna per questo concorso.').'</p>';
    return;
}
?>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Partecipante</th>
                <th class="text-center">Punti</th>
                <th class="text-center">Pronostici</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($colonne as $c):
                $pronostici = $dbo->fetchArray('SELECT pr.*, pa.squadra_casa, pa.squadra_ospite, pa.pannello FROM totocalcio_pronostici pr JOIN totocalcio_partite pa ON pa.id = pr.id_partita WHERE pr.id_colonna = '.prepare($c['id']).' ORDER BY pa.pannello, pa.ordine');
                $numOk = count(array_filter($pronostici, fn($pr) => $pr['punti'] > 0));
            ?>
            <tr>
                <td><strong><?php echo $c['partecipante']; ?></strong></td>
                <td class="text-center"><strong><?php echo (int)$c['punti_totali']; ?></strong></td>
                <td class="text-center">
                    <?php foreach ($pronostici as $pr):
                        $label = $pr['pronostico'];
                        if ($pr['tipo'] === 'risultato_esatto') $label = str_replace('-', '‑', $pr['pronostico']);
                        elseif ($pr['tipo'] === 'marcatore') $label = '⚽' . $pr['pronostico'];
                        $cls = $pr['punti'] > 0 ? 'success' : ($pr['punti'] === 0 && $pr['stato'] === 'finished' ? 'danger' : 'secondary');
                    ?>
                        <span class="badge badge-<?php echo $cls; ?>" title="<?php echo $pr['squadra_casa'].' - '.$pr['squadra_ospite'].' ('.$pr['tipo'].': '.$pr['pronostico'].')'; ?>"><?php echo $label; ?></span>
                    <?php endforeach; ?>
                    <span class="small text-muted">(<?php echo $numOk; ?>/<?php echo count($pronostici); ?>)</span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
