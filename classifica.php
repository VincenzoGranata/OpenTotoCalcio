<?php
require __DIR__ . '/config.inc.php';
require __DIR__ . '/modules/totomondiale/include/traduzioni.php';

try {
    $pdo = new PDO("mysql:host=$db_host;port=3306;dbname=$db_name;charset=utf8mb4", $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error');
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$partecipanti = $pdo->query('
    SELECT p.id, p.nome,
        (SELECT COALESCE(SUM(punti),0) FROM tot_pronostici WHERE id_partecipante = p.id) AS punti_partite,
        (SELECT COALESCE(SUM(punti),0) FROM tot_bonus WHERE id_partecipante = p.id) AS punti_bonus,
        (SELECT COALESCE(SUM(punti),0) FROM tot_pronostici WHERE id_partecipante = p.id) +
        (SELECT COALESCE(SUM(punti),0) FROM tot_bonus WHERE id_partecipante = p.id) AS totale
    FROM tot_partecipanti p
    ORDER BY totale DESC, p.nome ASC
')->fetchAll();

$partite = $pdo->query('
    SELECT id, girone, squadra_casa, squadra_ospite, goal_casa, goal_ospite, stato, data_partita, minuto
    FROM tot_partite WHERE LENGTH(girone) = 1
    ORDER BY data_partita ASC, id ASC
')->fetchAll();

$pronostici = $pdo->query('
    SELECT pr.id_partita, pr.id_partecipante, pr.pronostico, pr.punti
    FROM tot_pronostici pr
    JOIN tot_partite p ON pr.id_partita = p.id
    WHERE LENGTH(p.girone) = 1
')->fetchAll();

$bonus = $pdo->query('SELECT * FROM tot_bonus')->fetchAll();

$predMap = [];
foreach ($pronostici as $pr) {
    $predMap[$pr->id_partita][$pr->id_partecipante] = $pr;
}
$bonusMap = [];
foreach ($bonus as $b) {
    $bonusMap[$b->tipo][$b->id_partecipante] = $b;
}

$numP = count($partecipanti);
$colW = $numP > 20 ? 55 : 65;

$lr = '';
$pos = 1;
foreach ($partecipanti as $p) {
    $med = '';
    if ($pos === 1) $med = '<span class="med gold">1</span>';
    elseif ($pos === 2) $med = '<span class="med silver">2</span>';
    elseif ($pos === 3) $med = '<span class="med bronze">3</span>';
    else $med = '<span class="med neu">'.$pos.'</span>';
    $v = isset($bonusMap['vincente'][$p->id]) ? traduciSquadra($bonusMap['vincente'][$p->id]->valore) : '-';
    $c = isset($bonusMap['capocannoniere'][$p->id]) ? $bonusMap['capocannoniere'][$p->id]->valore : '-';
    $lr .= "<tr><td class=\"tc\">$med</td><td><strong>".e($p->nome)."</strong></td><td class=\"tc\">".(int)$p->punti_partite."</td><td class=\"tc\">".(int)$p->punti_bonus."</td><td class=\"tc\"><strong style=\"font-size:20px\">".(int)$p->totale."</strong></td><td class=\"tc\" style=\"font-size:0.75rem\">".e($v)."</td><td class=\"tc\" style=\"font-size:0.75rem\">".e($c)."</td></tr>\n";
    ++$pos;
}

$gruppi = [];
foreach ($partite as $m) { $gruppi[$m->girone][] = $m; }
ksort($gruppi);

$tg = '';
foreach ($gruppi as $girone => $matches) {
    $tg .= '<div class="cd mt-3">
        <div class="ch" onclick="toggleGruppo(\'g_'.e($girone).'\')" style="cursor:pointer;user-select:none">
            <div class="d-flex justify-content-between align-items-center">
                <h2 style="margin:0"><i class="fas fa-users"></i> Girone '.e($girone).'</h2>
                <span id="tg_'.e($girone).'" class="badge bg-secondary" style="font-size:0.8rem"><i class="fas fa-chevron-up"></i></span>
            </div>
        </div>
        <div id="g_'.e($girone).'" class="cb p0" style="overflow-x:auto"><table class="tb mb0" style="min-width:'.(280 + $numP * $colW).'px"><thead><tr><th style="width:180px">Partita</th><th style="width:70px" class="tc">Ris</th>';
    foreach ($partecipanti as $p) {
        $tg .= '<th class="tc" style="width:'.$colW.'px;writing-mode:vertical-lr;height:100px;vertical-align:bottom;font-size:0.6rem;padding:3px 1px;overflow:hidden" title="'.e($p->nome).'">'.e($p->nome).'</th>';
    }
    $tg .= "</tr></thead><tbody>\n";

    $puntiG = array_fill(0, $numP, 0);
    foreach ($matches as $m) {
        $gc = $m->goal_casa !== null ? (int)$m->goal_casa : null;
        $go = $m->goal_ospite !== null ? (int)$m->goal_ospite : null;
        $ris = ($gc !== null && $go !== null) ? "$gc-$go" : '?';
        $stato = $m->stato;
        $fin = $stato === 'finished';
        $live = $stato === 'ongoing';

        $sb = '';
        if ($fin) $sb = '<span class="badge bg-success">Terminata</span>';
        elseif ($live) $sb = '<span class="badge bg-warning text-dark">'.($m->minuto ? $m->minuto."'" : 'In corso').'</span>';
        else $sb = '<span class="badge bg-secondary">'.formatoOraBreve($m->data_partita).'</span>';

        $casa = traduciSquadra($m->squadra_casa);
        $ospite = traduciSquadra($m->squadra_ospite);
        $tg .= "<tr><td><small>".e($casa)."</small><br><small>".e($ospite)."</small></td><td class=\"tc\">$sb<br><strong>$ris</strong></td>";

        foreach ($partecipanti as $idx => $p) {
            $cell = '';
            $pr = $predMap[$m->id][$p->id] ?? null;
            if ($pr) {
                $v = $pr->pronostico;
                $pt = (int)$pr->punti;
                if ($fin) {
                    if ($pt > 0) { $cell = '<span class="pr pr-c">'.$v.'</span>'; $puntiG[$idx] += $pt; }
                    else $cell = '<span class="pr pr-w">'.$v.'</span>';
                } else $cell = '<span class="pr pr-p">'.$v.'</span>';
            } else $cell = '<span class="pr pr-e">-</span>';
            $tg .= '<td class="tc">'.$cell.'</td>';
        }
        $tg .= "</tr>\n";
    }

    $tg .= '<tr class="pr-row"><td colspan="2" class="tr fw-bold"><i class="fas fa-star"></i> Punti</td>';
    foreach ($partecipanti as $idx => $p) {
        $tg .= '<td class="tc fw-bold" style="font-size:1rem;color:#ffd700">'.$puntiG[$idx].'</td>';
    }
    $tg .= "</tr></tbody></table></div></div>\n";
}

$aggiornaToken = urlencode($aggiorna_token ?? 'toto2026');
$alertHtml = '';
if (!empty($_GET['aggiornato'])) {
    $partite = (int)($_GET['partite'] ?? 0);
    $punteggi = (int)($_GET['punteggi'] ?? 0);
    $alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size:0.85rem;margin-bottom:1rem">
        <i class="fas fa-check-circle"></i> Risultati aggiornati: <strong>' . $partite . '</strong> partite. Punti ricalcolati per <strong>' . $punteggi . '</strong> pronostici.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
} elseif (!empty($_GET['error'])) {
    $alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size:0.85rem;margin-bottom:1rem">
        <i class="fas fa-exclamation-triangle"></i> ' . e($_GET['error']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

echo '<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>TotoMondiale 2026</title>
<link rel="icon" type="image/webp" href="/assets/dist/img/logo_mondiali.webp">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body{background:#f5f6fa;min-height:100vh;padding-bottom:80px;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif}
.cd{border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);background:#fff}
.ch{background:#fff;border-bottom:2px solid #e0e0e0;padding:.8rem 1.2rem}
.ch h2{color:#333;font-weight:700;margin:0;font-size:1.1rem}
.ch h2 i{color:#e94560}
.ch1{border-bottom:2px solid #e94560}
.ch1 h1{color:#333}
.ch1 i{color:#ffd700}
.tb{margin-bottom:0;font-size:.82rem}
.tb thead th{background:#fafafa;color:#e94560;border-bottom:2px solid #e0e0e0;font-weight:700;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;padding:8px 4px;white-space:nowrap}
.tb tbody td{padding:6px 4px;vertical-align:middle;color:#444;border-color:#eee;font-size:.8rem}
.tb tbody tr:hover{background:#f0f0ff!important}
.tb-striped>tbody>tr:nth-of-type(odd){background:#fff}
.tb-striped>tbody>tr:nth-of-type(even){background:#fafbfc}
.pr-row td{background:#fff8e1!important;border-top:2px solid #ffc107!important;color:#e65100;font-weight:700}
.pr-row:hover{background:#fff3cd!important}
.med{display:inline-block;width:30px;height:30px;line-height:30px;border-radius:50%;font-weight:800;font-size:.82rem;text-align:center}
.med.gold{background:gold;color:#333}
.med.silver{background:silver;color:#333}
.med.bronze{background:#cd7f32;color:#fff}
.med.neu{background:#e0e0e0;color:#666}
.pr{display:inline-block;width:26px;height:26px;line-height:26px;border-radius:50%;font-weight:700;font-size:.75rem;text-align:center}
.pr-c{background:#4caf50;color:#fff}
.pr-w{background:#ef5350;color:#fff}
.pr-p{background:#bdbdbd;color:#fff}
.pr-e{background:transparent;color:#bbb;border:1px dashed #ccc}
.lg{background:#fff;border-radius:10px;padding:.8rem;border:1px solid #e0e0e0}
.lg p{color:#666;margin:4px 0 0;font-size:.78rem}
.ft{color:#999;font-size:.72rem;text-align:center;padding:1.2rem 0 0}
.rf{position:fixed;bottom:20px;right:20px;background:#e94560;color:#fff;border:none;border-radius:50%;width:48px;height:48px;font-size:20px;box-shadow:0 4px 15px rgba(233,69,96,.4);cursor:pointer;transition:all .3s;z-index:1000}
.rf:hover{transform:rotate(180deg);background:#ff6b81}
.ub{position:fixed;bottom:20px;right:80px;background:#4caf50;color:#fff;border:none;border-radius:50%;width:48px;height:48px;font-size:20px;box-shadow:0 4px 15px rgba(76,175,80,.4);cursor:pointer;transition:all .3s;z-index:1000}
.ub:hover{transform:scale(1.1);background:#66bb6a}
.ub:disabled{opacity:.5;cursor:wait}
.tc{text-align:center}
.tr{text-align:right}
.fw-bold{font-weight:700}
.mb0{margin-bottom:0}
.p0{padding:0}
.mt3{margin-top:1rem}
.container{max-width:100%;padding:10px}
@media(max-width:768px){
.tb{font-size:.7rem}.tb tbody td{padding:3px 2px}.pr{width:20px;height:20px;line-height:20px;font-size:.65rem}.med{width:24px;height:24px;line-height:24px;font-size:.7rem}.ch h2{font-size:.95rem}
}
</style>
</head>
<body>
<div class="container py-3">
' . $alertHtml . '
<div class="cd">
<div class="ch ch1 text-center" onclick="toggleClassifica()" style="cursor:pointer;user-select:none">
    <div class="d-flex justify-content-between align-items-center">
        <span></span>
        <h1 style="margin:0"><i class="fas fa-trophy"></i> TotoMondiale 2026</h1>
        <span id="toggle-classifica" class="badge bg-secondary" style="font-size:0.8rem"><i class="fas fa-chevron-up"></i></span>
    </div>
</div>
<div id="body-classifica">
<div class="cb p0" style="overflow-x:auto">
<table class="tb tb-striped mb0" style="min-width:450px">
<thead><tr><th class="tc" style="width:45px">#</th><th>Partecipante</th><th class="tc">Partite</th><th class="tc">Bonus</th><th class="tc" style="width:70px">Totale</th><th class="tc" style="width:100px">Vincitore</th><th class="tc" style="width:110px">Capocannoniere</th></tr></thead>
<tbody>'.$lr.'</tbody></table></div></div></div>

<div class="lg mt3"><div class="row text-center align-items-center g-2">
<div class="col-3"><span class="badge bg-success" style="font-size:.85rem">1</span> <span class="badge bg-danger" style="font-size:.85rem">X</span> <span class="badge bg-secondary" style="font-size:.85rem">2</span> <small class="text-muted ms-1">Esito</small></div>
<div class="col-3"><span class="badge bg-primary">1 pt</span> <small class="text-muted ms-1">Partita</small></div>
<div class="col-3"><span class="badge bg-success">3 pt</span> <small class="text-muted ms-1">Vincitore</small></div>
<div class="col-3"><span class="badge bg-warning text-dark">2 pt</span> <small class="text-muted ms-1">Capocannoniere</small></div>
</div>
<p class="text-center text-muted mt-2 mb-0" style="font-size:0.75rem"><i class="fas fa-info-circle"></i> Orari in fuso locale dello stadio (Canada, USA, Messico)</p>
</div>

'.$tg.'

<div class="cd mt-3">
<div class="ch" onclick="toggleRegolamento()" style="cursor:pointer;user-select:none">
    <div class="d-flex justify-content-between align-items-center">
        <h2 style="margin:0;font-size:1rem"><i class="fas fa-book"></i> Regolamento</h2>
        <span id="toggle-regolamento" class="badge bg-secondary" style="font-size:0.8rem"><i class="fas fa-chevron-down"></i></span>
    </div>
</div>
<div id="body-regolamento" class="cb" style="display:none;padding:1rem;font-size:0.85rem;line-height:1.6;background:#fafafa">
    <h5 style="color:#e94560">1. Iscrizione e Scadenze</h5>
    <ul>
        <li><strong>Quota di partecipazione:</strong> L\'iscrizione al torneo ha un costo di 10€.</li>
        <li><strong>Termine di consegna:</strong> Sia l\'invio dei pronostici che il saldo della quota di iscrizione devono avvenire rigorosamente entro e non oltre il 10 giugno 2026.</li>
        <li><strong>Modalità:</strong> I pronostici vanno consegnati all\'Amministratore in un\'unica soluzione. La mancata consegna o il mancato pagamento entro la scadenza comporteranno l\'esclusione dal gioco.</li>
    </ul>

    <h5 style="color:#e94560;margin-top:15px">2. Formato e Inserimento Pronostici</h5>
    <p>Il gioco è basato esclusivamente sulle partite della <strong>Fase a Gironi</strong> e su due pronostici finali.</p>
    <p>Al momento dell\'iscrizione, ogni partecipante dovrà fornire:</p>
    <ol>
        <li>Il pronostico (esito 1X2) per tutte le partite della fase a gironi.</li>
        <li>La squadra che vincerà il Mondiale.</li>
        <li>Il giocatore che vincerà il titolo di Capocannoniere del torneo.</li>
    </ol>

    <h5 style="color:#e94560;margin-top:15px">3. Sistema di Punteggio</h5>
    <p><strong>Partite della Fase a Gironi:</strong></p>
    <ul>
        <li><span class="badge bg-success">+1 Punto</span> Pronostico corretto (esito 1X2 indovinato)</li>
        <li><span class="badge bg-secondary">0 Punti</span> Pronostico errato</li>
    </ul>
    <p><strong>Pronostici Bonus (assegnati al termine del Mondiale):</strong></p>
    <ul>
        <li><span class="badge bg-success">+3 Punti</span> Se si indovina la Squadra Vincitrice del Mondiale</li>
        <li><span class="badge bg-warning text-dark">+2 Punti</span> Se si indovina il Capocannoniere del torneo</li>
    </ul>

    <h5 style="color:#e94560;margin-top:15px">4. Criteri di Spareggio</h5>
    <p>In caso di arrivo a pari punti in classifica generale, l\'assegnazione del montepremi verrà decisa in base ai seguenti criteri (in ordine di importanza):</p>
    <ol>
        <li>Aver indovinato la Squadra Vincitrice del Mondiale.</li>
        <li>Aver indovinato il Capocannoniere.</li>
        <li>In caso di ulteriore e totale parità, il premio verrà diviso in parti uguali tra i giocatori a pari merito.</li>
    </ol>

    <h5 style="color:#e94560;margin-top:15px">5. Suddivisione del Montepremi</h5>
    <p>Il montepremi totale è di <strong>650€</strong> così suddiviso:</p>
    <ul>
        <li><strong>1° classificato:</strong> 450€</li>
        <li><strong>2° classificato:</strong> 200€</li>
    </ul>
    <p>Se primo e secondo arrivano a pari punti in classifica, il montepremi totale verrà diviso in parti uguali tra i due.</p>
</div>
</div>

<div class="ft">
<i class="fas fa-sync-alt"></i> Auto-refresh 30s &middot;
<a href="#" onclick="esplodiTutto();return false" style="color:#e94560;margin-right:10px"><i class="fas fa-expand-alt"></i> Espandi tutti</a>
<a href="#" onclick="implodiTutto();return false" style="color:#e94560"><i class="fas fa-compress-alt"></i> Comprimi tutti</a>
&middot; <a href="https://worldcup26.ir" target="_blank" style="color:#e94560">worldcup26.ir</a>
</div>
</div>
<button class="rf" onclick="location.reload()" title="Aggiorna"><i class="fas fa-sync-alt"></i></button>
<button class="ub" onclick="aggiornaRisultati()" title="Aggiorna risultati da worldcup26.ir"><i class="fas fa-cloud-sun"></i></button>
<script>
function toggleClassifica() {
    var el = document.getElementById(\'body-classifica\');
    var badge = document.getElementById(\'toggle-classifica\');
    if (el.style.display === \'none\') {
        el.style.display = \'block\';
        badge.innerHTML = \'<i class="fas fa-chevron-up"></i>\';
    } else {
        el.style.display = \'none\';
        badge.innerHTML = \'<i class="fas fa-chevron-down"></i>\';
    }
}
function toggleRegolamento() {
    var el = document.getElementById(\'body-regolamento\');
    var badge = document.getElementById(\'toggle-regolamento\');
    if (el.style.display === \'none\') {
        el.style.display = \'block\';
        badge.innerHTML = \'<i class="fas fa-chevron-up"></i>\';
    } else {
        el.style.display = \'none\';
        badge.innerHTML = \'<i class="fas fa-chevron-down"></i>\';
    }
}
function toggleGruppo(id) {
    var el = document.getElementById(id);
    var g = id.replace(\'g_\', \'\');
    var badge = document.getElementById(\'tg_\' + g);
    if (el.style.display === \'none\') {
        el.style.display = \'block\';
        badge.innerHTML = \'<i class="fas fa-chevron-up"></i>\';
    } else {
        el.style.display = \'none\';
        badge.innerHTML = \'<i class="fas fa-chevron-down"></i>\';
    }
}
function esplodiTutto() {
    var els = document.querySelectorAll(\'[id^="g_"]\');
    for (var i = 0; i < els.length; i++) {
        els[i].style.display = \'block\';
        var g = els[i].id.replace(\'g_\', \'\');
        document.getElementById(\'tg_\' + g).innerHTML = \'<i class="fas fa-chevron-up"></i>\';
    }
}
function implodiTutto() {
    var els = document.querySelectorAll(\'[id^="g_"]\');
    for (var i = 0; i < els.length; i++) {
        els[i].style.display = \'none\';
        var g = els[i].id.replace(\'g_\', \'\');
        document.getElementById(\'tg_\' + g).innerHTML = \'<i class="fas fa-chevron-down"></i>\';
    }
}
function aggiornaRisultati() {
    var btn = document.querySelector(\'.ub\');
    btn.disabled = true;
    btn.innerHTML = \'<i class="fas fa-spinner fa-pulse"></i>\';
    fetch(\'aggiorna.php?token=' . $aggiornaToken . '\')
        .then(function() { location.reload(); })
        .catch(function() { location.reload(); });
}
function aggiornaSilenzioso() {
    var pos = window.scrollY;
    fetch(window.location.href).then(function(r) { return r.text(); }).then(function(html) {
        var nuovo = document.createElement(\'div\');
        nuovo.innerHTML = html;
        var nuovoBody = nuovo.querySelector(\'body\');
        if (nuovoBody) {
            document.body.innerHTML = nuovoBody.innerHTML;
            window.scrollTo(0, pos);
        }
    }).catch(function() {
        location.reload();
    });
}
setTimeout(function(){ aggiornaSilenzioso(); }, 30000)</script>
</body>
</html>';
