<?php

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

if (empty($id_record)) {
    echo '<div class="alert alert-info">'.tr('Nessun partecipante collegato al tuo account.').'</div>';
    return;
}

$punti_totali = $dbo->fetchOne('SELECT COALESCE(SUM(punti_totali),0) AS tot FROM totocalcio_colonne WHERE id_partecipante = '.prepare($id_record));
$num_colonne = $dbo->fetchOne('SELECT COUNT(*) AS cnt FROM totocalcio_colonne WHERE id_partecipante = '.prepare($id_record));
$totale_vincite = $dbo->fetchOne('SELECT COALESCE(SUM(importo),0) AS tot FROM totocalcio_vincite WHERE id_partecipante = '.prepare($id_record));
$record = $dbo->fetchOne('SELECT * FROM totocalcio_partecipanti WHERE id = '.prepare($id_record));
$concorso = $dbo->fetchOne("SELECT * FROM totocalcio_concorsi WHERE stato = 'aperto' ORDER BY giornata ASC LIMIT 1");
$has_giocata = $concorso ? $dbo->fetchOne('SELECT id FROM totocalcio_colonne WHERE id_partecipante = '.prepare($id_record).' AND id_concorso = '.prepare($concorso['id'])) : null;
?>

<style>
.mio-tc-card{background:#fff;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;margin-bottom:20px}
.mio-tc-card .card-body{padding:20px}
.mio-tc-stats{display:flex;justify-content:space-around;text-align:center;margin-top:15px}
.mio-tc-stat-number{font-size:26px;font-weight:800;margin:0;line-height:1.2}
.mio-tc-stat-label{font-size:12px;color:#888;text-transform:uppercase;letter-spacing:.5px;margin:4px 0 0}
.mio-tc-stat-blue{color:#4361ee}
.mio-tc-stat-cyan{color:#06d6a0}
.mio-tc-stat-green{color:#2ec4b6}
.mio-tc-name{font-size:20px;font-weight:700;color:#333;margin:0}
.mio-tc-name i{color:#4361ee;margin-right:8px}

.mio-tc-tabs{display:flex;gap:4px;background:#f0f2f5;border-radius:10px;padding:4px;margin-bottom:20px}
.mio-tc-tab{flex:1;padding:10px 14px;text-align:center;border-radius:8px;font-size:13px;font-weight:600;color:#666;cursor:pointer;transition:all .2s;text-decoration:none;border:none;background:transparent}
.mio-tc-tab:hover{color:#333;text-decoration:none}
.mio-tc-tab.active{background:#fff;color:#4361ee;box-shadow:0 2px 8px rgba(67,97,238,.15)}
.mio-tc-tab i{margin-right:6px;font-size:14px}

.mio-tc-alert{background:#eef5ff;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.mio-tc-alert i{color:#4361ee;font-size:16px}
.mio-tc-alert strong{color:#333;font-size:14px}
.mio-tc-alert .badge{font-size:11px;padding:3px 10px;border-radius:6px;margin-left:auto}
</style>

<div class="mio-tc-card">
    <div class="card-body">
        <div class="mio-tc-name"><i class="fa fa-user-circle"></i><?php echo $record['nome']; ?></div>
        <div class="mio-tc-stats">
            <div><div class="mio-tc-stat-number mio-tc-stat-blue"><?php echo (int)$punti_totali['tot']; ?></div><div class="mio-tc-stat-label">Punti</div></div>
            <div><div class="mio-tc-stat-number mio-tc-stat-cyan"><?php echo (int)$num_colonne['cnt']; ?></div><div class="mio-tc-stat-label">Colonne</div></div>
            <div><div class="mio-tc-stat-number mio-tc-stat-green"><?php echo number_format((float)$totale_vincite['tot'], 2, ',', '.'); ?>€</div><div class="mio-tc-stat-label">Vincite</div></div>
        </div>
    </div>
</div>

<?php if ($concorso): ?>
<div class="mio-tc-alert">
    <i class="fa fa-calendar-alt"></i>
    <div><strong><?php echo $concorso['nome']; ?></strong> &mdash; Chiusura: <?php echo date('d/m/Y H:i', strtotime($concorso['data_chiusura'])); ?></div>
    <?php if ($has_giocata): ?>
    <span class="badge badge-success ml-auto">Colonna inserita</span>
    <?php else: ?>
    <span class="badge badge-warning ml-auto">In attesa pronostico</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="mio-tc-tabs" id="mioTabBar">
    <span class="mio-tc-tab active" data-tab="tab-nuova-giocata"><i class="fa fa-futbol-o"></i>Nuova Giocata</span>
    <span class="mio-tc-tab" data-tab="tab-colonne"><i class="fa fa-list"></i>Le Mie Colonne</span>
    <span class="mio-tc-tab" data-tab="tab-vincite"><i class="fa fa-trophy"></i>Le Mie Vincite</span>
</div>

<div id="mioTabContent">
    <div id="tab-nuova-giocata" class="mio-tab-pane">
        <?php include __DIR__.'/plugins/nuova_giocata.php'; ?>
    </div>
    <div id="tab-colonne" class="mio-tab-pane" style="display:none">
        <?php include __DIR__.'/plugins/colonne.php'; ?>
    </div>
    <div id="tab-vincite" class="mio-tab-pane" style="display:none">
        <?php include __DIR__.'/plugins/vincite.php'; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#mioTabBar .mio-tc-tab').click(function() {
        var tabId = $(this).data('tab');
        $('#mioTabBar .mio-tc-tab').removeClass('active');
        $(this).addClass('active');
        $('.mio-tab-pane').hide();
        $('#' + tabId).show();
    });
});
</script>
