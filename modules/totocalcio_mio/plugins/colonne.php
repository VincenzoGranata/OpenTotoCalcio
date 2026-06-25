<?php

include_once __DIR__.'/../../../core.php';

$id_partecipante = $id_partecipante ?? $id_record ?? 0;
if (empty($id_partecipante)) { echo '<div class="alert alert-warning">'.tr('Partecipante non trovato').'</div>'; return; }

$colonne = $dbo->fetchArray('
    SELECT c.*, co.nome AS concorso, co.giornata, co.stato AS stato_concorso
    FROM totocalcio_colonne c
    JOIN totocalcio_concorsi co ON co.id = c.id_concorso
    WHERE c.id_partecipante = '.prepare($id_partecipante).'
    ORDER BY co.giornata DESC
');

if (empty($colonne)) {
    echo '<p class="text-muted">'.tr('Nessuna colonna inserita.').'</p>';
    return;
}
?>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Concorso</th>
                <th>Giornata</th>
                <th class="text-center">Punti</th>
                <th class="text-center">Pronostici</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($colonne as $c):
                $pronostici = $dbo->fetchArray('
                    SELECT pr.*, pa.squadra_casa, pa.squadra_ospite, pa.pannello, pa.stato
                    FROM totocalcio_pronostici pr
                    JOIN totocalcio_partite pa ON pa.id = pr.id_partita
                    WHERE pr.id_colonna = '.prepare($c['id']).'
                    ORDER BY FIELD(pa.pannello, \'obbligatorio\', \'obbligatorio_esatto\', \'opzionale_scelta\'), pa.ordine
                ');
                $numOk = count(array_filter($pronostici, fn($pr) => $pr['punti'] > 0));
            ?>
            <tr>
                <td><?php echo $c['concorso']; ?></td>
                <td><?php echo $c['giornata']; ?>ª</td>
                <td class="text-center"><strong><?php echo (int)$c['punti_totali']; ?></strong></td>
                <td class="text-center">
                    <?php foreach ($pronostici as $pr):
                        $label = $pr['pronostico'];
                        if ($pr['tipo'] === 'risultato_esatto') $label = str_replace('-', '‑', $pr['pronostico']);
                        $cls = $pr['punti'] > 0 ? 'success' : ($pr['punti'] === 0 && $pr['stato'] === 'finished' ? 'danger' : 'secondary');
                        $tt = $pr['squadra_casa'].' - '.$pr['squadra_ospite'].' ('.$pr['tipo'].': '.$pr['pronostico'].')';
                    ?>
                        <span class="badge badge-<?php echo $cls; ?>" title="<?php echo $tt; ?>"><?php echo $label; ?></span>
                    <?php endforeach; ?>
                    <span class="small text-muted">(<?php echo $numOk; ?>/<?php echo count($pronostici); ?>)</span>
                </td>
                <td><?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
