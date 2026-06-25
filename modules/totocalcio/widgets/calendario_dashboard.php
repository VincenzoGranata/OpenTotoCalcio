<?php

include_once __DIR__.'/../../../core.php';

if (!empty($is_title_request)) {
    echo tr('Calendario Serie A');
    return;
}

$concorsi = \database()->fetchArray('
    SELECT c.*,
        (SELECT COUNT(*) FROM totocalcio_partite WHERE id_concorso = c.id) AS totale,
        (SELECT COUNT(*) FROM totocalcio_partite WHERE id_concorso = c.id AND stato = \'finished\') AS completate,
        (SELECT COUNT(*) FROM totocalcio_partite WHERE id_concorso = c.id AND stato = \'ongoing\') AS in_corso,
        (SELECT MIN(data_partita) FROM totocalcio_partite WHERE id_concorso = c.id) AS prima_partita
    FROM totocalcio_concorsi c
    ORDER BY c.giornata ASC
');

$moduleIdCalendario = \database()->fetchOne('SELECT id FROM zz_modules WHERE directory = \'totocalcio_calendario\'');
$totaleGiornate = count($concorsi);
$completate = count(array_filter($concorsi, fn($c) => $c['completate'] > 0 && $c['completate'] == $c['totale']));
$serieALogo = 'data:image/png;base64,' . base64_encode(file_get_contents(__DIR__ . '/../../../assets/src/img/logo_seriea.png'));
?>
<style>
.cal-dash-giornata {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 4px;
    cursor: pointer;
    transition: background 0.2s;
    background: #fff;
}
.cal-dash-giornata:hover {
    background: #f8f9ff;
}
.cal-dash-header {
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    user-select: none;
}
.cal-dash-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}
.cal-dash-dot.completed { background: #28a745; }
.cal-dash-dot.ongoing { background: #ffc107; }
.cal-dash-dot.upcoming { background: #adb5bd; }
.cal-dash-body {
    display: none;
    padding: 0 12px 12px;
    border-top: 1px solid #eee;
}
.cal-dash-body.open { display: block; }
.cal-dash-match {
    display: flex;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    gap: 8px;
    font-size: 0.85rem;
}
.cal-dash-match:last-child { border-bottom: none; }
.cal-dash-match:hover { background: #f0f0ff; }
.cal-dash-score {
    font-weight: 700;
    min-width: 36px;
    text-align: center;
}
.cal-dash-badge { font-size: 0.65rem; padding: 1px 6px; border-radius: 4px; white-space: nowrap; }
.cal-dash-dots { display: flex; gap: 2px; align-items: center; margin-left: auto; }
.cal-dash-dot-mini { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.cal-dash-dot-mini.finished { background: #28a745; }
.cal-dash-dot-mini.ongoing { background: #ffc107; }
.cal-dash-dot-mini.scheduled { background: #dee2e6; }
.cal-dash-arrow { font-size: 0.7rem; color: #999; margin-left: 5px; }
.cal-dash-summary {
    display: flex; gap: 20px; padding: 8px 12px;
    background: linear-gradient(135deg, #1E2D48, #2C3E6B);
    border-radius: 8px; margin-bottom: 8px;
    align-items: center;
    color: #fff;
}
.cal-dash-summary img.serie-a-logo { height: 40px; }
.cal-dash-summary .summary-title { font-size: 1rem; font-weight: 700; }
.cal-dash-summary .summary-subtitle { font-size: 0.75rem; opacity: 0.8; }
.cal-dash-summary .ml-auto a { 
    background: rgba(255,255,255,0.15); 
    color: #fff; 
    border: 1px solid rgba(255,255,255,0.3);
}
.cal-dash-summary .ml-auto a:hover { background: rgba(255,255,255,0.3); color: #fff; }
</style>

<div style="max-height:600px;overflow-y:auto">
    <div class="cal-dash-summary">
        <img src="<?php echo $serieALogo; ?>" class="serie-a-logo" alt="Serie A">
        <div>
            <div class="summary-title">Calendario Serie A</div>
            <div class="summary-subtitle"><?php echo $completate; ?>/<?php echo $totaleGiornate; ?> giornate completate</div>
        </div>
        <div class="ml-auto text-right">
            <a class="btn btn-sm" href="<?php echo base_path_osm(); ?>/controller.php?id_module=<?php echo $moduleIdCalendario['id']; ?>">
                <i class="fa fa-external-link"></i> Apri
            </a>
        </div>
    </div>

<?php foreach ($concorsi as $co):
    $statoCls = 'upcoming';
    $statoLabel = 'Futura';
    if ($co['completate'] > 0 && $co['completate'] == $co['totale']) {
        $statoCls = 'completed';
        $statoLabel = 'Completata';
    } elseif ($co['in_corso'] > 0 || ($co['completate'] > 0 && $co['completate'] < $co['totale'])) {
        $statoCls = 'ongoing';
        $statoLabel = 'In corso';
    }
    $dataLabel = $co['prima_partita'] ? date('d/m', strtotime($co['prima_partita'])) : '-';
    $dots = '';
    $partite = \database()->fetchArray('SELECT id, squadra_casa, squadra_ospite, goal_casa, goal_ospite, logo_casa, logo_ospite, stato, data_partita FROM totocalcio_partite WHERE id_concorso = '.prepare($co['id']).' ORDER BY data_partita ASC, ordine ASC');
    foreach ($partite as $p) {
        $cls = $p['stato'] === 'finished' ? 'finished' : ($p['stato'] === 'ongoing' ? 'ongoing' : 'scheduled');
        $dots .= '<span class="cal-dash-dot-mini '.$cls.'"></span>';
    }
?>
<div class="cal-dash-giornata" data-concorso="<?php echo $co['id']; ?>">
    <div class="cal-dash-header" onclick="toggleGiornata(this.parentNode)">
        <span class="cal-dash-dot <?php echo $statoCls; ?>" title="<?php echo $statoLabel; ?>"></span>
        <strong>Giornata <?php echo $co['giornata']; ?></strong>
        <small class="text-muted"><?php echo $dataLabel; ?></small>
        <div class="cal-dash-dots"><?php echo $dots; ?></div>
        <span class="text-muted" style="font-size:0.75rem">(<?php echo (int)$co['completate']; ?>/<?php echo (int)$co['totale']; ?>)</span>
        <span class="cal-dash-arrow"><i class="fa fa-chevron-down"></i></span>
    </div>
    <div class="cal-dash-body">
        <?php foreach ($partite as $p):
            $logoCasa = $p['logo_casa'] ? '<img src="'.$p['logo_casa'].'" style="height:16px;width:16px;object-fit:contain">' : '';
            $logoOspite = $p['logo_ospite'] ? '<img src="'.$p['logo_ospite'].'" style="height:16px;width:16px;object-fit:contain">' : '';
            $ris = ($p['goal_casa'] !== null && $p['goal_ospite'] !== null) ? $p['goal_casa'].'-'.$p['goal_ospite'] : '-';
            $badge = '';
            if ($p['stato'] === 'scheduled') $badge = '<span class="badge bg-info cal-dash-badge">Prog.</span>';
            elseif ($p['stato'] === 'ongoing') $badge = '<span class="badge bg-warning text-dark cal-dash-badge">'.($p['data_partita'] ? date('H:i', strtotime($p['data_partita'])) : 'In corso').'</span>';
            elseif ($p['stato'] === 'finished') $badge = '<span class="badge bg-success cal-dash-badge">Fin.</span>';
        ?>
        <div class="cal-dash-match" onclick="openPartitaModal(<?php echo $p['id']; ?>)">
            <?php echo $logoCasa; ?>
            <span style="flex:1;text-align:right"><?php echo $p['squadra_casa']; ?></span>
            <span class="cal-dash-score"><?php echo $ris; ?></span>
            <span style="flex:1"><?php echo $p['squadra_ospite']; ?></span>
            <?php echo $logoOspite; ?>
            <?php echo $badge; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<script>
function toggleGiornata(el) {
    var body = el.querySelector('.cal-dash-body');
    var arrow = el.querySelector('.cal-dash-arrow i');
    body.classList.toggle('open');
    arrow.className = body.classList.contains('open') ? 'fa fa-chevron-up' : 'fa fa-chevron-down';
    if (!document.getElementById('partitaModal')) {
        var m = document.createElement('div');
        m.className = 'modal fade'; m.id = 'partitaModal'; m.setAttribute('tabindex', '-1');
        m.innerHTML = '<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Dettaglio Partita</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body" id="partitaModalBody"><div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div></div></div></div>';
        document.body.appendChild(m);
    }
}
function openPartitaModal(id) {
    var body = document.getElementById('partitaModalBody');
    if (!body) return;
    body.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';
    $("#partitaModal").modal("show");
    fetch('<?php echo base_path_osm(); ?>/actions.php?id_module=<?php echo $moduleIdCalendario['id'] ?? 121; ?>&id_record=' + id + '&op=dettaglio_partita')
        .then(function(r) { return r.text(); })
        .then(function(html) { body.innerHTML = html; })
        .catch(function() { body.innerHTML = '<div class="alert alert-danger">Errore</div>'; });
}
</script>
