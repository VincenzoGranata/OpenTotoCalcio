<?php

include_once __DIR__.'/../../../core.php';

$id_partecipante = $id_record;

// Trova il concorso aperto più recente
$concorso = $dbo->fetchOne("
    SELECT * FROM totocalcio_concorsi
    WHERE stato = 'aperto'
    ORDER BY giornata ASC
    LIMIT 1
");

if (!$concorso) {
    echo '<div class="alert alert-info">'.tr('Nessun concorso aperto al momento.').'</div>';

    // Mostra l'ultimo concorso concluso
    $ultimo = $dbo->fetchOne("SELECT * FROM totocalcio_concorsi WHERE stato = 'concluso' ORDER BY giornata DESC LIMIT 1");
    if ($ultimo) {
        $colonna = $dbo->fetchOne('SELECT id FROM totocalcio_colonne WHERE id_partecipante = '.prepare($id_partecipante).' AND id_concorso = '.prepare($ultimo['id']));
        if ($colonna) {
            echo '<div class="alert alert-success">'.tr('Il tuo pronostico per '.$ultimo['nome'].' è stato registrato.').'</div>';
        }
    }
    return;
}

// Verifica se ha già una colonna
$existing = $dbo->fetchOne('SELECT id, punti_totali FROM totocalcio_colonne WHERE id_partecipante = '.prepare($id_partecipante).' AND id_concorso = '.prepare($concorso['id']));
if ($existing) {
    echo '<div class="alert alert-info">'.tr('Hai già inserito la tua colonna per '.$concorso['nome'].'. Punti attuali: '.$existing['punti_totali']).'</div>';
    return;
}

$partite = $dbo->fetchArray('SELECT * FROM totocalcio_partite WHERE id_concorso = '.prepare($concorso['id']).' ORDER BY pannello, ordine');
if (empty($partite)) {
    echo '<div class="alert alert-warning">'.tr('Nessuna partita disponibile per questo concorso.').'</div>';
    return;
}

$obbligatori = array_filter($partite, fn($p) => $p['pannello'] === 'obbligatorio');
$opzionali = array_filter($partite, fn($p) => $p['pannello'] === 'opzionale');
?>
<form action="" method="post" id="form-nuova-giocata">
    <input type="hidden" name="op" value="save_colonna">
    <input type="hidden" name="id_partecipante" value="<?php echo $id_partecipante; ?>">
    <input type="hidden" name="id_concorso" value="<?php echo $concorso['id']; ?>">

    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i>
        <strong><?php echo $concorso['nome']; ?></strong> —
        Chiusura: <?php echo date('d/m/Y H:i', strtotime($concorso['data_chiusura'])); ?>
    </div>

    <!-- OBBLIGATORI -->
    <div class="card card-primary">
        <div class="card-header"><h4>Obbligatori (7) — 1pt cad.</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>#</th><th>Casa</th><th>Ospite</th><th class="text-center" style="width:180px">1</th><th class="text-center" style="width:180px">X</th><th class="text-center" style="width:180px">2</th></tr></thead>
                    <tbody>
                        <?php foreach ($obbligatori as $m): ?>
                        <tr>
                            <td><?php echo $m['ordine']; ?></td>
                            <td><?php echo $m['squadra_casa']; ?></td>
                            <td><?php echo $m['squadra_ospite']; ?></td>
                            <td class="text-center">
                                <label class="btn btn-outline-secondary btn-sm" style="cursor:pointer">
                                    <input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="1" class="pronostico-radio" data-match="<?php echo $m['id']; ?>" data-type="1x2" required>
                                    1
                                </label>
                            </td>
                            <td class="text-center">
                                <label class="btn btn-outline-secondary btn-sm" style="cursor:pointer">
                                    <input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="X" class="pronostico-radio" data-match="<?php echo $m['id']; ?>" data-type="1x2">
                                    X
                                </label>
                            </td>
                            <td class="text-center">
                                <label class="btn btn-outline-secondary btn-sm" style="cursor:pointer">
                                    <input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="2" class="pronostico-radio" data-match="<?php echo $m['id']; ?>" data-type="1x2">
                                    2
                                </label>
                            </td>
                        </tr>
                        <input type="hidden" name="pronostici[<?php echo $m['id']; ?>][tipo]" value="1x2">
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- OPZIONALI -->
    <div class="card card-warning">
        <div class="card-header"><h4>Opzionali (3) — Assegna ogni partita a una categoria</h4></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>#</th><th>Casa</th><th>Ospite</th><th class="text-center">Scelta 1X2 (1pt)</th><th class="text-center">Risultato Esatto (3pt)</th><th class="text-center">Marcatore (2pt)</th><th style="width:200px">Pronostico</th></tr></thead>
                    <tbody>
                        <?php foreach ($opzionali as $m):
                            $giocatori = $dbo->fetchArray('
                                SELECT g.id, g.nome FROM totocalcio_giocatori g
                                JOIN totocalcio_squadre s ON s.id = g.id_squadra
                                WHERE s.nome IN ('.prepare($m['squadra_casa']).', '.prepare($m['squadra_ospite']).')
                                ORDER BY g.nome ASC
                            ');
                        ?>
                        <tr class="opzionale-row" data-match="<?php echo $m['id']; ?>">
                            <td><?php echo $m['ordine']; ?></td>
                            <td class="squadra-casa"><?php echo $m['squadra_casa']; ?></td>
                            <td class="squadra-ospite"><?php echo $m['squadra_ospite']; ?></td>
                            <td class="text-center">
                                <input type="radio" name="opz_cat[<?php echo $m['id']; ?>]" value="1x2" class="opz-cat" data-match="<?php echo $m['id']; ?>" required>
                            </td>
                            <td class="text-center">
                                <input type="radio" name="opz_cat[<?php echo $m['id']; ?>]" value="risultato_esatto" class="opz-cat" data-match="<?php echo $m['id']; ?>">
                            </td>
                            <td class="text-center">
                                <input type="radio" name="opz_cat[<?php echo $m['id']; ?>]" value="marcatore" class="opz-cat" data-match="<?php echo $m['id']; ?>">
                            </td>
                            <td>
                                <div class="input-1x2" style="display:none">
                                    <label class="btn btn-outline-secondary btn-sm"><input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="1"> 1</label>
                                    <label class="btn btn-outline-secondary btn-sm"><input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="X"> X</label>
                                    <label class="btn btn-outline-secondary btn-sm"><input type="radio" name="pronostici[<?php echo $m['id']; ?>][pronostico]" value="2"> 2</label>
                                </div>
                                <div class="input-esatto" style="display:none">
                                    <input type="number" name="pronostici[<?php echo $m['id']; ?>][pronostico]" class="form-control form-control-sm" style="width:80px;display:inline-block" placeholder="Casa" min="0" max="20"> -
                                    <input type="number" name="pronostici[<?php echo $m['id']; ?>][pronostico2]" class="form-control form-control-sm" style="width:80px;display:inline-block" placeholder="Ospite" min="0" max="20">
                                </div>
                                <div class="input-marcatore" style="display:none">
                                    <select name="pronostici[<?php echo $m['id']; ?>][pronostico]" class="form-control form-control-sm select2" style="width:180px">
                                        <option value="">Seleziona...</option>
                                        <?php foreach ($giocatori as $g): ?>
                                        <option value="<?php echo $g['id']; ?>"><?php echo $g['nome']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
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
    $('.select2').select2();

    $('.opz-cat').change(function() {
        var matchId = $(this).data('match');
        var val = $(this).val();
        var row = $('.opzionale-row[data-match="' + matchId + '"]');

        row.find('.input-1x2, .input-esatto, .input-marcatore').hide();
        row.find('.input-' + val).show();

        row.find('input[type="radio"][name^="pronostici[' + matchId + ']"]').prop('required', val === '1x2');
        row.find('input[type="number"][name^="pronostici[' + matchId + ']"]').prop('required', val === 'risultato_esatto');
        row.find('select[name^="pronostici[' + matchId + ']"]').prop('required', val === 'marcatore');
    });

    function checkValid() {
        var obblOk = $('.pronostico-radio:checked').length >= <?php echo count($obbligatori); ?>;
        var opzCatOk = $('.opz-cat:checked').length === <?php echo count($opzionali); ?>;

        var cats = {};
        $('.opz-cat:checked').each(function() { cats[$(this).val()] = (cats[$(this).val()] || 0) + 1; });

        var catOk = cats['1x2'] === 1 && cats['risultato_esatto'] === 1 && cats['marcatore'] === 1;

        var opzFilled = true;
        $('.opz-cat:checked').each(function() {
            var matchId = $(this).data('match');
            var val = $(this).val();
            var row = $('.opzionale-row[data-match="' + matchId + '"]');
            if (val === '1x2') {
                if (!row.find('input[type="radio"][name^="pronostici[' + matchId + ']"]:checked').length) opzFilled = false;
            } else if (val === 'risultato_esatto') {
                if (!row.find('input[name^="pronostici[' + matchId + ']"][type="number"]').filter(function() { return $(this).val() !== ''; }).length) opzFilled = false;
            } else if (val === 'marcatore') {
                if (!row.find('select[name^="pronostici[' + matchId + ']"]').val()) opzFilled = false;
            }
        });

        $('#btn-invia-colonna').prop('disabled', !(obblOk && opzCatOk && catOk && opzFilled));
    }

    $('input, select').on('change keyup', checkValid);
    $('.pronostico-radio, .opz-cat').on('change', checkValid);

    $('#btn-invia-colonna').click(function() {
        if ($(this).prop('disabled')) return;

        // Combina risultato esatto inputs
        $('.input-esatto').each(function() {
            var container = $(this).closest('td');
            var inputs = container.find('input[type="number"]');
            if (inputs.length === 2) {
                var val = inputs.eq(0).val() + '-' + inputs.eq(1).val();
                container.find('input[type="hidden"]').remove();
                $('<input>').attr({type: 'hidden', name: inputs.eq(0).attr('name'), value: val}).appendTo(container);
                inputs.eq(1).remove();
            }
        });

        $('#form-nuova-giocata').submit();
    });
});
</script>
