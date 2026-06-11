<?php

include_once __DIR__.'/../../../core.php';
include_once __DIR__.'/../include/traduzioni.php';

$id_partecipante = $id_record;
$partite = $dbo->fetchArray('SELECT p.*, pr.pronostico, pr.punti FROM tot_partite p LEFT JOIN tot_pronostici pr ON pr.id_partita = p.id AND pr.id_partecipante = '.prepare($id_partecipante).' WHERE LENGTH(p.girone) = 1 ORDER BY p.data_partita ASC');

if (empty($partite)) {
    echo '<p>'.tr('Nessuna partita disponibile. Carica prima le partite da Sofascore.').'</p>';
    return;
}
?>
<form action="" method="post" id="pronostici-form">
    <input type="hidden" name="op" value="save_pronostico">
    <input type="hidden" name="id_partecipante" value="<?php echo $id_partecipante; ?>">
    <input type="hidden" name="id_partita" id="pronostico_id_partita" value="">
    <input type="hidden" name="pronostico" id="pronostico_valore" value="">
</form>

<div class="form-group mb-3">
    <input type="text" id="filtro-squadre" class="form-control" placeholder="<?php echo tr('Cerca squadra...'); ?>">
</div>

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="tabella-pronostici">
        <thead>
            <tr>
                <th>Data</th>
                <th>Girone</th>
                <th>Casa</th>
                <th>Risultato</th>
                <th>Ospite</th>
                <th class="text-center">1</th>
                <th class="text-center">X</th>
                <th class="text-center">2</th>
                <th>Punti</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($partite as $partita): 
                $risultato = ($partita['goal_casa'] !== null && $partita['goal_ospite'] !== null) ? $partita['goal_casa'].'-'.$partita['goal_ospite'] : '?';
                $casa = traduciSquadra($partita['squadra_casa']);
                $ospite = traduciSquadra($partita['squadra_ospite']);
            ?>
            <tr class="riga-partita">
                <td class="data-partita"><?php echo formatoOraBreve($partita['data_partita']); ?></td>
                <td><?php echo $partita['girone']; ?></td>
                <td class="squadra-casa"><?php echo $casa; ?></td>
                <td class="text-center"><?php echo $risultato; ?></td>
                <td class="squadra-ospite"><?php echo $ospite; ?></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm <?php echo $partita['pronostico'] === '1' ? 'btn-success' : 'btn-outline-secondary'; ?> pronostico-btn" data-id="<?php echo $partita['id']; ?>" data-valore="1" <?php echo $partita['stato'] === 'finished' ? 'disabled' : ''; ?>>1</button>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm <?php echo $partita['pronostico'] === 'X' ? 'btn-success' : 'btn-outline-secondary'; ?> pronostico-btn" data-id="<?php echo $partita['id']; ?>" data-valore="X" <?php echo $partita['stato'] === 'finished' ? 'disabled' : ''; ?>>X</button>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm <?php echo $partita['pronostico'] === '2' ? 'btn-success' : 'btn-outline-secondary'; ?> pronostico-btn" data-id="<?php echo $partita['id']; ?>" data-valore="2" <?php echo $partita['stato'] === 'finished' ? 'disabled' : ''; ?>>2</button>
                </td>
                <td class="text-center">
                    <?php if ($partita['stato'] === 'finished'): ?>
                        <span class="badge badge-<?php echo $partita['punti'] > 0 ? 'success' : 'secondary'; ?>">
                            <?php echo $partita['punti'] ?: '0'; ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
$(document).ready(function() {
    $('#filtro-squadre').on('keyup', function() {
        var q = $(this).val().toLowerCase();
        $('#tabella-pronostici tbody .riga-partita').each(function() {
            var casa = $(this).find('.squadra-casa').text().toLowerCase();
            var ospite = $(this).find('.squadra-ospite').text().toLowerCase();
            if (casa.indexOf(q) !== -1 || ospite.indexOf(q) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    $('.pronostico-btn').click(function() {
        var btn = $(this);
        var id = btn.data('id');
        var valore = btn.data('valore');

        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                op: 'save_pronostico',
                id_partecipante: <?php echo $id_partecipante; ?>,
                id_partita: id,
                pronostico: valore,
                ajax: 1
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    var row = btn.closest('tr');
                    row.find('.pronostico-btn').removeClass('btn-success').addClass('btn-outline-secondary');
                    btn.removeClass('btn-outline-secondary').addClass('btn-success');
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message);
                    }
                }
            },
            error: function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Errore nel salvataggio');
                }
            }
        });
    });
});
</script>
