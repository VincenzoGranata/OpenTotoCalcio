<?php

include_once __DIR__.'/../../../core.php';

$id_partecipante = $id_partecipante ?? $id_record ?? 0;
if (empty($id_partecipante)) { echo '<div class="alert alert-warning">'.tr('Partecipante non trovato').'</div>'; return; }

$concorso = $dbo->fetchOne("SELECT * FROM totocalcio_concorsi WHERE stato = 'aperto' ORDER BY giornata ASC LIMIT 1");
if (!$concorso) {
    echo '<div class="alert alert-info">'.tr('Nessun concorso aperto al momento.').'</div>';
    $ultimo = $dbo->fetchOne("SELECT * FROM totocalcio_concorsi WHERE stato = 'concluso' ORDER BY giornata DESC LIMIT 1");
    if ($ultimo) {
        $colonna = $dbo->fetchOne('SELECT id, punti_totali FROM totocalcio_colonne WHERE id_partecipante = '.prepare($id_partecipante).' AND id_concorso = '.prepare($ultimo['id']));
        if ($colonna) {
            echo '<div class="alert alert-success">'.tr('Ultimo concorso: '.$ultimo['nome'].' — Punti: '.$colonna['punti_totali']).'</div>';
        }
    }
    return;
}

$existing = $dbo->fetchOne('SELECT id, punti_totali FROM totocalcio_colonne WHERE id_partecipante = '.prepare($id_partecipante).' AND id_concorso = '.prepare($concorso['id']));
if ($existing) {
    echo '<div class="alert alert-success">'.tr('Colonna già inserita per '.$concorso['nome'].' — Punti: '.$existing['punti_totali']).'</div>';
    return;
}

$partite = $dbo->fetchArray('SELECT * FROM totocalcio_partite WHERE id_concorso = '.prepare($concorso['id']).' ORDER BY FIELD(pannello, \'obbligatorio\', \'obbligatorio_esatto\', \'opzionale_scelta\'), ordine');
if (empty($partite)) {
    echo '<div class="alert alert-warning">'.tr('Nessuna partita disponibile per questo concorso.').'</div>';
    return;
}

$obbligatori = array_filter($partite, fn($p) => $p['pannello'] === 'obbligatorio');
$esattoMatch = current(array_filter($partite, fn($p) => $p['esatto'] == 1));
$sceltaMatches = array_filter($partite, fn($p) => $p['pannello'] === 'opzionale_scelta');

$panelsAssigned = count($obbligatori) < count($partite) || !empty($sceltaMatches);
$readonly = !$panelsAssigned;
$countObbl = count($obbligatori);
$countScelta = count($sceltaMatches);
?>

<?php if ($readonly): ?>
<div class="alert alert-warning">
    <i class="fa fa-info-circle"></i>
    I pannelli non sono ancora stati assegnati. L'amministratore deve cliccare "Assegna Pannelli" in Concorsi Totocalcio.
    <strong>Visualizzazione in sola lettura delle <?php echo count($partite); ?> partite.</strong>
</div>

<div class="card">
    <div class="card-header"><h4>Partite di <?php echo $concorso['nome']; ?></h4></div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0">
            <thead><tr><th>#</th><th>Casa</th><th>Ospite</th></tr></thead>
            <tbody>
                <?php foreach ($partite as $m): ?>
                <tr>
                    <td><?php echo $m['ordine']; ?></td>
                    <td><?php echo ($m['logo_casa'] ? '<img src="'.$m['logo_casa'].'" style="height:16px;width:16px;margin-right:4px">' : '').$m['squadra_casa']; ?></td>
                    <td><?php echo ($m['logo_ospite'] ? '<img src="'.$m['logo_ospite'].'" style="height:16px;width:16px;margin-right:4px">' : '').$m['squadra_ospite']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<form action="<?php echo base_path_osm(); ?>/editor.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>" method="post" id="form-nuova-giocata">
    <input type="hidden" name="op" value="save_colonna">
    <input type="hidden" name="id_partecipante" value="<?php echo $id_partecipante; ?>">
    <input type="hidden" name="id_concorso" value="<?php echo $concorso['id']; ?>">

    <!-- OBBLIGATORI -->
    <div class="card card-primary">
        <div class="card-header"><h4>Obbligatori (7) — 1pt cad.</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>#</th><th>Casa</th><th>Ospite</th><th class="text-center" style="width:150px">1</th><th class="text-center" style="width:150px">X</th><th class="text-center" style="width:150px">2</th></tr></thead>
                    <tbody>
                        <?php foreach ($obbligatori as $m): ?>
                        <tr>
                            <td><?php echo $m['ordine']; ?></td>
                            <td><?php echo ($m['logo_casa'] ? '<img src="'.$m['logo_casa'].'" style="height:16px;width:16px;margin-right:4px">' : '').$m['squadra_casa']; ?></td>
                            <td><?php echo ($m['logo_ospite'] ? '<img src="'.$m['logo_ospite'].'" style="height:16px;width:16px;margin-right:4px">' : '').$m['squadra_ospite']; ?></td>
                            <td class="text-center"><label class="btn btn-outline-secondary btn-sm" style="cursor:pointer"><input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="1" class="pronostico-radio" required> 1</label></td>
                            <td class="text-center"><label class="btn btn-outline-secondary btn-sm" style="cursor:pointer"><input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="X" class="pronostico-radio"> X</label></td>
                            <td class="text-center"><label class="btn btn-outline-secondary btn-sm" style="cursor:pointer"><input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="2" class="pronostico-radio"> 2</label></td>
                        </tr>
                        <input type="hidden" name="pronostici[<?php echo $m['id']; ?>][tipo]" value="1x2">
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RISULTATO ESATTO -->
    <?php if ($esattoMatch): ?>
    <div class="card card-success">
        <div class="card-header"><h4>Risultato Esatto (extra su 1 partita random) — 3pt</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Casa</th><th></th><th>Ospite</th></tr></thead>
                    <tbody>
                        <tr>
                            <td class="text-center fw-bold" style="font-size:1.2rem"><?php echo ($esattoMatch['logo_casa'] ? '<img src="'.$esattoMatch['logo_casa'].'" style="height:20px;width:20px;vertical-align:middle;margin-right:4px">' : '').$esattoMatch['squadra_casa']; ?></td>
                            <td style="width:200px">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <input type="number" name="esatto[<?php echo $esattoMatch['id']; ?>][pronostico]" class="form-control form-control-sm" style="width:70px" placeholder="Casa" min="0" max="20" required>
                                    <span class="fw-bold">:</span>
                                    <input type="number" name="esatto[<?php echo $esattoMatch['id']; ?>][pronostico2]" class="form-control form-control-sm" style="width:70px" placeholder="Ospite" min="0" max="20" required>
                                </div>
                                <input type="hidden" name="esatto[<?php echo $esattoMatch['id']; ?>][tipo]" value="risultato_esatto">
                            </td>
                            <td class="text-center fw-bold" style="font-size:1.2rem"><?php echo ($esattoMatch['logo_ospite'] ? '<img src="'.$esattoMatch['logo_ospite'].'" style="height:20px;width:20px;vertical-align:middle;margin-right:4px">' : '').$esattoMatch['squadra_ospite']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- SCELTA -->
    <div class="card card-warning">
        <div class="card-header"><h4>Scelta (1 su <?php echo max($countScelta, 0); ?>) — Scegli 1 partita — 1pt</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>#</th><th>Casa</th><th>Ospite</th><th class="text-center">Scegli</th><th style="width:250px">Pronostico 1X2</th></tr></thead>
                    <tbody>
                        <?php foreach ($sceltaMatches as $m): ?>
                        <tr class="scelta-row" data-match="<?php echo $m['id']; ?>">
                            <td><?php echo $m['ordine']; ?></td>
                            <td><?php echo ($m['logo_casa'] ? '<img src="'.$m['logo_casa'].'" style="height:16px;width:16px;margin-right:4px">' : '').$m['squadra_casa']; ?></td>
                            <td><?php echo ($m['logo_ospite'] ? '<img src="'.$m['logo_ospite'].'" style="height:16px;width:16px;margin-right:4px">' : '').$m['squadra_ospite']; ?></td>
                            <td class="text-center">
                                <input type="radio" name="scelta" value="<?php echo $m['id']; ?>" class="scelta-radio" required>
                            </td>
                            <td>
                                <div class="scelta-1x2" style="display:none">
                                    <label class="btn btn-outline-secondary btn-sm"><input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="1"> 1</label>
                                    <label class="btn btn-outline-secondary btn-sm"><input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="X"> X</label>
                                    <label class="btn btn-outline-secondary btn-sm"><input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="2"> 2</label>
                                    <input type="hidden" name="pronostici[<?php echo $m['id']; ?>][tipo]" value="1x2">
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button type="button" class="btn btn-primary btn-lg btn-block" id="btn-invia-colonna" disabled>
        <i class="fa fa-check"></i> Invia Colonna
    </button>
</form>

<script>
$(document).ready(function() {
    $('.scelta-radio').change(function() {
        var matchId = $(this).val();
        $('.scelta-row').each(function() {
            var row = $(this);
            var id = row.data('match');
            if (id == matchId) {
                row.find('.scelta-1x2').show();
                row.find('input[type="radio"][name^="pronostici[' + id + ']"]').prop('required', true);
            } else {
                row.find('.scelta-1x2').hide();
                row.find('input[type="radio"][name^="pronostici[' + id + ']"]').prop('required', false).prop('checked', false);
            }
        });
    });

    function checkValid() {
        var obblOk = $('.pronostico-radio:checked').length >= <?php echo count($obbligatori); ?>;
        var sceltaOk = $('.scelta-radio:checked').length === 1;
        var sceltaFilled = true;
        if (sceltaOk) {
            var chosenId = $('.scelta-radio:checked').val();
            sceltaFilled = $('input[type="radio"][name^="pronostici[' + chosenId + ']"]:checked').length >= 1;
        } else { sceltaFilled = false; }
        var esattoOk = true;
        <?php if ($esattoMatch): ?>
        esattoOk = $('input[name="esatto[<?php echo $esattoMatch['id']; ?>][pronostico]"]').val() !== ''
            && $('input[name="esatto[<?php echo $esattoMatch['id']; ?>][pronostico2]"]').val() !== '';
        <?php endif; ?>
        $('#btn-invia-colonna').prop('disabled', !(obblOk && sceltaOk && sceltaFilled && esattoOk));
    }

    $('input, select').on('change keyup', checkValid);
    $('.pronostico-radio, .scelta-radio').on('change', checkValid);

    $('#btn-invia-colonna').click(function() {
        if ($(this).prop('disabled')) return;
        <?php if ($esattoMatch): ?>
        var ec = $('input[name="esatto[<?php echo $esattoMatch['id']; ?>][pronostico]"]').closest('td');
        var ei = ec.find('input[type="number"]');
        if (ei.length === 2) {
            var val = ei.eq(0).val() + '-' + ei.eq(1).val();
            ei.eq(0).attr('name', 'esatto[<?php echo $esattoMatch['id']; ?>][pronostico]');
            $('<input>').attr({type: 'hidden', name: 'esatto[<?php echo $esattoMatch['id']; ?>][pronostico]', value: val}).appendTo(ec);
            ei.eq(1).remove();
        }
        <?php endif; ?>
        var chosen = $('.scelta-radio:checked').val();
        $('.scelta-row').each(function() {
            if ($(this).data('match') != chosen) {
                $(this).find('input[name^="pronostici[' + $(this).data('match') + ']"]').prop('disabled', true);
            }
        });
        $('#form-nuova-giocata').submit();
    });
});
</script>
<?php endif; ?>
