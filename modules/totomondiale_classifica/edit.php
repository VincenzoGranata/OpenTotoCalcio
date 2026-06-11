<?php

include_once __DIR__.'/../../core.php';

$classifica = $dbo->fetchArray('
    SELECT 
        p.id,
        p.nome,
        (SELECT COALESCE(SUM(punti),0) FROM tot_pronostici WHERE id_partecipante = p.id) AS punti_partite,
        (SELECT COALESCE(SUM(punti),0) FROM tot_bonus WHERE id_partecipante = p.id) AS punti_bonus,
        (SELECT COALESCE(SUM(punti),0) FROM tot_pronostici WHERE id_partecipante = p.id) + 
        (SELECT COALESCE(SUM(punti),0) FROM tot_bonus WHERE id_partecipante = p.id) AS totale
    FROM tot_partecipanti p
    ORDER BY totale DESC
');

$totale_partite = $dbo->fetchOne('SELECT COUNT(*) AS tot FROM tot_partite WHERE LENGTH(girone) = 1 AND stato = \'finished\'');
$match_giocati = $totale_partite['tot'] ?? 0;
?>
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-trophy"></i> Classifica TotoMondiale</h3>
            </div>
            <div class="card-body">
                <?php if (empty($classifica)): ?>
                <p class="text-muted">Nessun partecipante. Aggiungili dal modulo TotoMondiale.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:60px">#</th>
                                <th>Partecipante</th>
                                <th class="text-center">Pronostici</th>
                                <th class="text-center">Bonus</th>
                                <th class="text-center">Totale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pos = 1;
                            foreach ($classifica as $p): 
                                $medaglia = '';
                                if ($pos === 1) $medaglia = '<span class="badge" style="background-color: gold; color: #333; font-size: 16px;">1</span>';
                                elseif ($pos === 2) $medaglia = '<span class="badge" style="background-color: silver; color: #333; font-size: 16px;">2</span>';
                                elseif ($pos === 3) $medaglia = '<span class="badge" style="background-color: #cd7f32; color: #333; font-size: 16px;">3</span>';
                                else $medaglia = '<span class="badge badge-secondary" style="font-size: 14px;">'.$pos.'</span>';
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $medaglia; ?></td>
                                <td><strong><?php echo $p['nome']; ?></strong></td>
                                <td class="text-center"><?php echo $p['punti_partite']; ?></td>
                                <td class="text-center"><?php echo $p['punti_bonus']; ?></td>
                                <td class="text-center">
                                    <strong style="font-size: 18px;"><?php echo $p['totale']; ?></strong>
                                </td>
                            </tr>
                            <?php 
                            ++$pos;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-info-circle"></i> Legenda Punteggi</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <span class="badge badge-primary" style="font-size: 18px;">1 pt</span>
                        <p class="mt-2">Pronostico 1/X/2<br><small class="text-muted">per ogni partita azzeccata</small></p>
                    </div>
                    <div class="col-md-4 text-center">
                        <span class="badge badge-success" style="font-size: 18px;">3 pt</span>
                        <p class="mt-2">Vincitore Mondiale<br><small class="text-muted">se indovinato</small></p>
                    </div>
                    <div class="col-md-4 text-center">
                        <span class="badge badge-warning" style="font-size: 18px;">2 pt</span>
                        <p class="mt-2">Capocannoniere<br><small class="text-muted">se indovinato</small></p>
                    </div>
                </div>
                <hr>
                <p class="text-muted text-center mb-0">
                    <i class="fa fa-refresh"></i> 
                    I punteggi vengono aggiornati automaticamente quando carichi i risultati da <strong>Partite Mondiale → Aggiorna Risultati e Punteggi</strong>
                </p>
            </div>
        </div>
    </div>
</div>
