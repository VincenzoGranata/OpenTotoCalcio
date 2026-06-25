<?php

include_once __DIR__.'/../../core.php';

$classifica = $dbo->fetchArray('
    SELECT p.id, p.nome,
        COALESCE(SUM(c.punti_totali), 0) AS totale
    FROM totocalcio_partecipanti p
    LEFT JOIN totocalcio_colonne c ON c.id_partecipante = p.id
    GROUP BY p.id, p.nome
    ORDER BY totale DESC, p.nome ASC
');

$miniClassifiche = $dbo->fetchArray('SELECT * FROM totocalcio_mini_classifiche ORDER BY data_inizio DESC');
?>
<div class="row mb-3">
    <div class="col-md-12">

        <!-- TABS -->
        <ul class="nav nav-tabs" id="classifica-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-generale" data-toggle="tab" href="#classifica-generale" role="tab">
                    <i class="fa fa-trophy"></i> Generale
                </a>
            </li>
            <?php foreach ($miniClassifiche as $mc): ?>
            <li class="nav-item">
                <a class="nav-link" id="tab-mini-<?php echo $mc['id']; ?>" data-toggle="tab" href="#classifica-mini-<?php echo $mc['id']; ?>" role="tab">
                    <i class="fa fa-medal"></i> <?php echo $mc['nome']; ?>
                    <?php if ($mc['stato'] === 'attiva'): ?>
                        <span class="badge badge-success">ATTIVA</span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content">

            <!-- TAB GENERALE -->
            <div class="tab-pane active" id="classifica-generale" role="tabpanel">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-trophy"></i> Classifica Generale Totocalcio</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($classifica)): ?>
                        <p class="text-muted">Nessun partecipante.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:60px">#</th>
                                        <th>Partecipante</th>
                                        <th class="text-center">Punti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $pos = 1; foreach ($classifica as $p):
                                        $med = '';
                                        if ($pos === 1) $med = '<span class="badge" style="background-color:gold;color:#333;font-size:16px">1</span>';
                                        elseif ($pos === 2) $med = '<span class="badge" style="background-color:silver;color:#333;font-size:16px">2</span>';
                                        elseif ($pos === 3) $med = '<span class="badge" style="background-color:#cd7f32;color:#fff;font-size:16px">3</span>';
                                        else $med = '<span class="badge badge-secondary" style="font-size:14px">'.$pos.'</span>';
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $med; ?></td>
                                        <td><strong><?php echo $p['nome']; ?></strong></td>
                                        <td class="text-center"><strong style="font-size:18px"><?php echo (int)$p['totale']; ?></strong></td>
                                    </tr>
                                    <?php ++$pos; endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB MINI CLASSIFICHE -->
            <?php foreach ($miniClassifiche as $mc):
                $classificaMini = (new TotoCalcio\CalcoloPuntiService)->calcolaClassificaRange($mc['data_inizio'], $mc['data_fine']);
                $premi = $dbo->fetchArray('SELECT * FROM totocalcio_mini_classifiche_premi WHERE id_mini_classifica = '.prepare($mc['id']).' ORDER BY posizione');
                $vincite = $dbo->fetchArray('SELECT * FROM totocalcio_vincite WHERE id_mini_classifica = '.prepare($mc['id']));
                $vinciteMap = [];
                foreach ($vincite as $v) $vinciteMap[$v['id_partecipante']] = $v;
            ?>
            <div class="tab-pane" id="classifica-mini-<?php echo $mc['id']; ?>" role="tabpanel">
                <div class="card card-<?php echo $mc['stato'] === 'attiva' ? 'success' : 'secondary'; ?>">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-medal"></i> <?php echo $mc['nome']; ?></h3>
                        <div class="card-tools">
                            <small class="text-muted">
                                <?php echo date('d/m/Y', strtotime($mc['data_inizio'])); ?>
                                → <?php echo $mc['data_fine'] ? date('d/m/Y', strtotime($mc['data_fine'])) : 'in corso'; ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($classificaMini)): ?>
                        <p class="text-muted">Nessun dato nel periodo.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:60px">#</th>
                                        <th>Partecipante</th>
                                        <th class="text-center">Punti</th>
                                        <th class="text-center">Premio</th>
                                        <th class="text-center">Stato</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $pos = 1; foreach ($classificaMini as $p):
                                        $med = $pos === 1 ? '<span class="badge" style="background-color:gold;color:#333;font-size:16px">1</span>' : ($pos === 2 ? '<span class="badge" style="background-color:silver;color:#333;font-size:16px">2</span>' : ($pos === 3 ? '<span class="badge" style="background-color:#cd7f32;color:#fff;font-size:16px">3</span>' : '<span class="badge badge-secondary" style="font-size:14px">'.$pos.'</span>'));
                                        $premio = '';
                                        foreach ($premi as $pr) { if ((int)$pr['posizione'] === $pos) { $premio = number_format($pr['importo'], 2, ',', '.').'€'; break; } }
                                        $v = $vinciteMap[$p['id']] ?? null;
                                        $pagato = $v && $v['pagato'] ? '<span class="badge badge-success">Pagato</span>' : ($v ? '<span class="badge badge-warning">In attesa</span>' : '-');
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $med; ?></td>
                                        <td><strong><?php echo $p['nome']; ?></strong></td>
                                        <td class="text-center"><strong style="font-size:18px"><?php echo (int)$p['totale']; ?></strong></td>
                                        <td class="text-center"><?php echo $premio ?: '-'; ?></td>
                                        <td class="text-center"><?php echo $pagato; ?></td>
                                    </tr>
                                    <?php ++$pos; endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>

        <!-- LEGENDA -->
        <div class="card card-info mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-info-circle"></i> Legenda Punteggi</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <span class="badge badge-primary" style="font-size:18px">1 pt</span>
                        <p class="mt-2">Pronostico 1/X/2<br><small class="text-muted">Obbligatori e Scelta (1 su 3)</small></p>
                    </div>
                    <div class="col-md-3 text-center">
                        <span class="badge badge-success" style="font-size:18px">3 pt</span>
                        <p class="mt-2">Risultato Esatto<br><small class="text-muted">Opzionale dedicato</small></p>
                    </div>
                    <div class="col-md-3 text-center">
                        <span class="badge badge-info" style="font-size:18px">1 pt</span>
                        <p class="mt-2">Scelta (1 su 3)<br><small class="text-muted">Scegli 1 partita su 3</small></p>
                    </div>
                    <div class="col-md-3 text-center">
<span class="badge" style="font-size:18px;background:#e94560;color:#fff">11 pt</span>
<p class="mt-2">Massimo per giornata<br><small class="text-muted">7 obbligatori + 1 scelta (1 su 3) + 1 esatto = 11pt</small></p>
                    </div>
                </div>
                <hr>
                <p class="text-muted text-center mb-0">
                    <i class="fa fa-refresh"></i>
                    I punteggi vengono aggiornati da <strong>Concorsi Totocalcio → Aggiorna Risultati e Punti</strong>
                </p>
            </div>
        </div>

    </div>
</div>
