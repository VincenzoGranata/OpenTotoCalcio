<?php

include_once __DIR__.'/../../../core.php';

$id_concorso = $id_record;
$concorso = $dbo->fetchOne('SELECT * FROM totocalcio_concorsi WHERE id = '.prepare($id_concorso));

$partite = $dbo->fetchArray('SELECT * FROM totocalcio_partite WHERE id_concorso = '.prepare($id_concorso).' ORDER BY data_partita ASC, ordine ASC');

if (empty($partite)):
    echo '<p>'.tr('Nessuna partita per questa giornata.').'</p>';
    return;
endif;
?>
<style>
.partita-row{cursor:pointer}
.partita-row:hover td{background:#f0f0ff!important}
</style>
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th class="text-center" style="width:40px">#</th>
                <th>Data</th>
                <th>Squadra Casa</th>
                <th class="text-center" style="width:70px">Risultato</th>
                <th>Squadra Ospite</th>
                <th class="text-center">Stato</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($partite as $i => $p):
                $data = $p['data_partita'] ? date('d/m/Y H:i', strtotime($p['data_partita'])) : '-';
                $logoCasa = $p['logo_casa'] ? '<img src="'.$p['logo_casa'].'" style="height:18px;margin-right:5px">' : '';
                $logoOspite = $p['logo_ospite'] ? '<img src="'.$p['logo_ospite'].'" style="height:18px;margin-left:5px">' : '';
                $risultato = ($p['goal_casa'] !== null && $p['goal_ospite'] !== null) ? $p['goal_casa'].'-'.$p['goal_ospite'] : '-';
                $badge = '';
                if ($p['stato'] === 'scheduled') $badge = '<span class="badge bg-info">Programmata</span>';
                elseif ($p['stato'] === 'ongoing') $badge = '<span class="badge bg-warning text-dark">'.$p['minuto'].'\'</span>';
                elseif ($p['stato'] === 'finished') $badge = '<span class="badge bg-success">Terminata</span>';
                else $badge = '<span class="badge bg-secondary">'.$p['stato'].'</span>';
            ?>
            <tr class="partita-row" onclick="openPartitaModal(<?php echo $p['id']; ?>)" title="Clicca per dettagli">
                <td class="text-center"><?php echo $i + 1; ?></td>
                <td style="white-space:nowrap"><?php echo $data; ?></td>
                <td><?php echo $logoCasa.' '.$p['squadra_casa']; ?></td>
                <td class="text-center fw-bold"><?php echo $risultato; ?></td>
                <td><?php echo $p['squadra_ospite'].' '.$logoOspite; ?></td>
                <td class="text-center"><?php echo $badge; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Dettaglio Partita Modal -->
<script>
function openPartitaModal(id) {
    var modal = document.getElementById("partitaModal");
    if (!modal) {
        // Create modal if not in DOM
        modal = document.createElement("div");
        modal.className = "modal fade";
        modal.id = "partitaModal";
        modal.setAttribute("tabindex", "-1");
        modal.innerHTML = '<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Dettaglio Partita</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body" id="partitaModalBody"><div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div></div></div></div>';
        document.body.appendChild(modal);
    }
    var body = document.getElementById("partitaModalBody");
    body.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>';
    $("#partitaModal").modal("show");

    fetch("<?php echo base_path_osm(); ?>/actions.php?id_module=<?php echo $id_module; ?>&id_record=" + id + "&op=dettaglio_partita")
        .then(function(r) { return r.text(); })
        .then(function(html) {
            body.innerHTML = html;
        })
        .catch(function() {
            body.innerHTML = '<div class="alert alert-danger">Errore nel caricamento</div>';
        });
}
</script>
