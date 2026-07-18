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
                <th class="text-center">Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($colonne as $c):
                $pronostici = $dbo->fetchArray('
                    SELECT pr.*, pa.squadra_casa, pa.squadra_ospite, pa.pannello, pa.stato, pa.goal_casa, pa.goal_ospite
                    FROM totocalcio_pronostici pr
                    JOIN totocalcio_partite pa ON pa.id = pr.id_partita
                    WHERE pr.id_colonna = '.prepare($c['id']).'
                    ORDER BY pa.pannello, pa.ordine
                ');
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
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modal-colonna-<?php echo $c['id']; ?>">
                        <i class="fa fa-eye"></i> Dettagli
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php foreach ($colonne as $c):
    $pronostici = $dbo->fetchArray('
        SELECT pr.*, pa.squadra_casa, pa.squadra_ospite, pa.pannello, pa.stato, pa.goal_casa, pa.goal_ospite
        FROM totocalcio_pronostici pr
        JOIN totocalcio_partite pa ON pa.id = pr.id_partita
        WHERE pr.id_colonna = '.prepare($c['id']).'
        ORDER BY pa.pannello, pa.ordine
    ');
?>
<div class="modal fade" id="modal-colonna-<?php echo $c['id']; ?>" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <?php echo $c['partecipante']; ?> - Giornata <?php echo $id_concorso; ?>
                    <small class="text-muted">(<?php echo $c['punti_totali']; ?> pt)</small>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Pannello</th>
                                <th>Partita</th>
                                <th>Pronostico</th>
                                <th>Risultato</th>
                                <th>Punti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pronostici as $pr):
                                $pannelloLabel = match($pr['pannello']) {
                                    'obbligatorio' => 'OB',
                                    'opzionale_scelta' => 'SC',
                                    default => 'OP'
                                };
                                $risultato = ($pr['goal_casa'] !== null && $pr['goal_ospite'] !== null)
                                    ? $pr['goal_casa'] . '-' . $pr['goal_ospite']
                                    : ($pr['stato'] === 'finished' ? '-' : 'Non iniziata');
                            ?>
                                <tr>
                                    <td><span class="badge badge-secondary"><?php echo $pannelloLabel; ?></span></td>
                                    <td><?php echo $pr['squadra_casa']; ?> vs <?php echo $pr['squadra_ospite']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $pr['punti'] > 0 ? 'success' : ($pr['punti'] === 0 && $pr['stato'] === 'finished' ? 'danger' : 'secondary'); ?>">
                                            <?php echo $pr['pronostico']; ?>
                                        </span>
                                        <?php if ($pr['tipo'] === 'risultato_esatto'): ?>
                                            <small class="text-muted">(Esatto)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $risultato; ?></td>
                                    <td class="text-center"><strong><?php echo (int)$pr['punti']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted text-center mt-2">
                    Totale: <strong><?php echo $c['punti_totali']; ?> pt</strong>
                    Inserita il: <?php echo date('d/m/Y H:i', strtotime($c['created_at'])); ?>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
