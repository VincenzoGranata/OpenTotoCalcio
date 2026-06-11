<?php

include_once __DIR__.'/../../../core.php';
include_once __DIR__.'/../include/traduzioni.php';

$id_partecipante = $id_record;

$bonus = $dbo->fetchArray('SELECT * FROM tot_bonus WHERE id_partecipante = '.prepare($id_partecipante));
$bonusMap = [];
foreach ($bonus as $b) {
    $bonusMap[$b['tipo']] = $b;
}
?>
<div class="row">
    <div class="col-md-6">
        <form action="" method="post">
            <input type="hidden" name="op" value="save_bonus">
            <input type="hidden" name="id_partecipante" value="<?php echo $id_partecipante; ?>">
            <input type="hidden" name="tipo" value="vincente">

            <div class="card card-primary">
                <div class="card-header">
                    <h5 class="card-title">Vincitore Mondiale (3 pt)</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Squadra</label>
                        <select name="valore" class="form-control select2" onchange="this.form.submit()">
                            <option value="">Seleziona...</option>
                            <?php
                            $squadre = $dbo->fetchArray('SELECT DISTINCT squadra_casa AS nome FROM tot_partite UNION SELECT DISTINCT squadra_ospite FROM tot_partite ORDER BY nome');
                            foreach ($squadre as $s):
                                $selected = (isset($bonusMap['vincente']) && $bonusMap['vincente']['valore'] === $s['nome']) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $s['nome']; ?>" <?php echo $selected; ?>><?php echo traduciSquadra($s['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (isset($bonusMap['vincente']) && $bonusMap['vincente']['punti'] > 0): ?>
                    <div class="text-center">
                        <span class="badge badge-success"><?php echo $bonusMap['vincente']['punti']; ?> punti</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="col-md-6">
        <form action="" method="post">
            <input type="hidden" name="op" value="save_bonus">
            <input type="hidden" name="id_partecipante" value="<?php echo $id_partecipante; ?>">
            <input type="hidden" name="tipo" value="capocannoniere">

            <div class="card card-primary">
                <div class="card-header">
                    <h5 class="card-title">Capocannoniere (2 pt)</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Giocatore</label>
                        <input type="text" name="valore" class="form-control" value="<?php echo isset($bonusMap['capocannoniere']) ? $bonusMap['capocannoniere']['valore'] : ''; ?>" placeholder="Nome giocatore" onchange="this.form.submit()">
                    </div>
                    <?php if (isset($bonusMap['capocannoniere']) && $bonusMap['capocannoniere']['punti'] > 0): ?>
                    <div class="text-center">
                        <span class="badge badge-success"><?php echo $bonusMap['capocannoniere']['punti']; ?> punti</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>
