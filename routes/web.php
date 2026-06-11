<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/../modules/totomondiale/include/traduzioni.php';

Route::get('/login-redirect', function (Request $request) {
    $url = $request->url();
    if (stripos($url, '/public/') !== false) {
        return redirect(substr($url, 0, stripos($url, 'public')));
    }
})->name('login');

Route::get('/classifica', function (Request $request) {
    $aggiornaToken = urlencode('toto2026');

    $alertHtml = '';
    if ($request->has('aggiornato')) {
        $partite = (int)$request->input('partite', 0);
        $punteggi = (int)$request->input('punteggi', 0);
        $alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size:0.85rem;margin-bottom:1rem">
            <i class="fas fa-check-circle"></i> Risultati aggiornati: <strong>' . $partite . '</strong> partite. Punti ricalcolati per <strong>' . $punteggi . '</strong> pronostici.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    } elseif ($request->has('error')) {
        $alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size:0.85rem;margin-bottom:1rem">
            <i class="fas fa-exclamation-triangle"></i> ' . e($request->input('error')) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }

    $partecipanti = DB::select('
        SELECT
            p.id, p.nome,
            (SELECT COALESCE(SUM(punti),0) FROM tot_pronostici WHERE id_partecipante = p.id) AS punti_partite,
            (SELECT COALESCE(SUM(punti),0) FROM tot_bonus WHERE id_partecipante = p.id) AS punti_bonus,
            (SELECT COALESCE(SUM(punti),0) FROM tot_pronostici WHERE id_partecipante = p.id) +
            (SELECT COALESCE(SUM(punti),0) FROM tot_bonus WHERE id_partecipante = p.id) AS totale
        FROM tot_partecipanti p
        ORDER BY totale DESC, p.nome ASC
    ');

    $partite = DB::select('
        SELECT id, girone, squadra_casa, squadra_ospite, goal_casa, goal_ospite, stato, data_partita, minuto
        FROM tot_partite WHERE LENGTH(girone) = 1
        ORDER BY data_partita ASC, id ASC
    ');

    $pronostici = DB::select('
        SELECT pr.id_partita, pr.id_partecipante, pr.pronostico, pr.punti
        FROM tot_pronostici pr
        JOIN tot_partite p ON pr.id_partita = p.id
        WHERE LENGTH(p.girone) = 1
    ');

    $bonus = DB::select('SELECT * FROM tot_bonus');

    $predMap = [];
    foreach ($pronostici as $pr) {
        $predMap[$pr->id_partita][$pr->id_partecipante] = $pr;
    }

    $bonusMap = [];
    foreach ($bonus as $b) {
        $bonusMap[$b->tipo][$b->id_partecipante] = $b;
    }

    $numPartecipanti = count($partecipanti);
    $colWidth = $numPartecipanti > 20 ? 60 : 70;

    $leaderboardRows = '';
    $pos = 1;
    foreach ($partecipanti as $p) {
        $med = '';
        if ($pos === 1) $med = '<span class="med gold">1</span>';
        elseif ($pos === 2) $med = '<span class="med silver">2</span>';
        elseif ($pos === 3) $med = '<span class="med bronze">3</span>';
        else $med = '<span class="med neutral">'.$pos.'</span>';
        $v = isset($bonusMap['vincente'][$p->id]) ? traduciSquadra($bonusMap['vincente'][$p->id]->valore) : '-';
        $c = isset($bonusMap['capocannoniere'][$p->id]) ? $bonusMap['capocannoniere'][$p->id]->valore : '-';
        $leaderboardRows .= "<tr><td class=\"text-center\">$med</td><td><strong>".e($p->nome)."</strong></td><td class=\"text-center\">".(int)$p->punti_partite."</td><td class=\"text-center\">".(int)$p->punti_bonus."</td><td class=\"text-center\"><strong style=\"font-size:20px\">".(int)$p->totale."</strong></td><td class=\"text-center\" style=\"font-size:0.75rem\">".e($v)."</td><td class=\"text-center\" style=\"font-size:0.75rem\">".e($c)."</td></tr>\n";
        ++$pos;
    }

    $gruppi = [];
    foreach ($partite as $m) {
        $gruppi[$m->girone][] = $m;
    }
    ksort($gruppi);

    function getResult($g) { return $g === null ? '?' : ($g > 0 ? '1' : ($g < 0 ? '2' : 'X')); }

    $tabelleGironi = '';
    foreach ($gruppi as $girone => $matches) {
        $tabelleGironi .= "<div class=\"card mt-3\">
            <div class=\"card-header\" onclick=\"toggleGruppo('gruppo_$girone')\" style=\"cursor:pointer;user-select:none\">
                <div class=\"d-flex justify-content-between align-items-center\">
                    <h2 class=\"mb-0\"><i class=\"fas fa-users\"></i> Girone $girone</h2>
                    <span id=\"toggle_$girone\" class=\"badge bg-secondary\" style=\"font-size:0.8rem\"><i class=\"fas fa-chevron-up\"></i></span>
                </div>
            </div>
            <div id=\"gruppo_$girone\" class=\"card-body p-0\" style=\"overflow-x:auto\">
            <table class=\"table table-striped mb-0\" style=\"min-width:".(300 + $numPartecipanti * $colWidth)."px\"><thead><tr><th style=\"width:180px\">Partita</th><th style=\"width:70px\" class=\"text-center\">Ris</th>";
        foreach ($partecipanti as $p) {
            $tabelleGironi .= '<th class="text-center" style="width:'.$colWidth.'px;writing-mode:vertical-lr;height:100px;vertical-align:bottom;font-size:0.6rem;white-space:nowrap;padding:4px 2px;overflow:hidden" title="'.e($p->nome).'">'.e($p->nome).'</th>';
        }
        $tabelleGironi .= "</tr></thead><tbody>\n";

        $puntiGirone = array_fill(0, $numPartecipanti, 0);
        foreach ($matches as $m) {
            $goalCasa = $m->goal_casa !== null ? (int)$m->goal_casa : null;
            $goalOspite = $m->goal_ospite !== null ? (int)$m->goal_ospite : null;
            $risultato = ($goalCasa !== null && $goalOspite !== null) ? $goalCasa.'-'.$goalOspite : '?';
            $risultatoSym = ($goalCasa !== null && $goalOspite !== null) ? getResult($goalCasa - $goalOspite) : null;
            $stato = $m->stato;
            $isFinished = $stato === 'finished';
            $isLive = $stato === 'ongoing';

            $statusBadge = '';
            if ($isFinished) $statusBadge = '<span class="badge bg-success">Terminata</span>';
            elseif ($isLive) $statusBadge = '<span class="badge bg-warning text-dark">'.($m->minuto ? $m->minuto."'" : 'In corso').'</span>';
            else $statusBadge = '<span class="badge bg-secondary">'.formatoOraBreve($m->data_partita).'</span>';

            $casa = traduciSquadra($m->squadra_casa);
            $ospite = traduciSquadra($m->squadra_ospite);
            $tabelleGironi .= "<tr><td><small>".e($casa)."</small><br><small>".e($ospite)."</small></td><td class=\"text-center\">$statusBadge<br><strong>$risultato</strong></td>";

            foreach ($partecipanti as $idx => $p) {
                $cell = '';
                $pr = $predMap[$m->id][$p->id] ?? null;
                if ($pr) {
                    $val = $pr->pronostico;
                    $pt = (int)$pr->punti;
                    if ($isFinished) {
                        if ($pt > 0) {
                            $cell = '<span class="pred pred-correct">'.$val.'</span>';
                            $puntiGirone[$idx] += $pt;
                        } else {
                            $cell = '<span class="pred pred-wrong">'.$val.'</span>';
                        }
                    } else {
                        $cell = '<span class="pred pred-pending">'.$val.'</span>';
                    }
                } else {
                    $cell = '<span class="pred pred-empty">-</span>';
                }
                $tabelleGironi .= '<td class="text-center">'.$cell.'</td>';
            }
            $tabelleGironi .= "</tr>\n";
        }

        $tabelleGironi .= "<tr class=\"punti-row\"><td colspan=\"2\" class=\"text-end fw-bold\"><i class=\"fas fa-star\"></i> Punti girone</td>";
        foreach ($partecipanti as $idx => $p) {
            $tabelleGironi .= '<td class="text-center fw-bold" style="font-size:1.05rem;color:#ffd700">'.$puntiGirone[$idx].'</td>';
        }
        $tabelleGironi .= "</tr></tbody></table></div></div>\n";
    }

    return '
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classifica TotoMondiale 2026</title>
    <link rel="icon" type="image/webp" href="/assets/dist/img/logo_mondiali.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; padding-bottom: 80px; }
        .card { border: none; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.25); }
        .card-header { background: linear-gradient(135deg, #1a1a2e, #16213e); border-bottom: 2px solid #e94560; padding: 1rem 1.25rem; }
        .card-header h1, .card-header h2 { color: #fff; font-weight: 700; margin: 0; font-size: 1.2rem; }
        .card-header h1 i { color: #ffd700; }
        .card-header h2 i { color: #e94560; font-size: 1rem; }
        .table { margin-bottom: 0; font-size: 0.85rem; }
        .table thead th { background: #1a1a2e; color: #e94560; border-bottom: 2px solid #e94560; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 10px 6px; white-space: nowrap; }
        .table tbody td { padding: 8px 6px; vertical-align: middle; color: #e0e0e0; border-color: #2a2a4a; }
        .table tbody tr:hover { background: rgba(233,69,96,0.08) !important; }
        .table-striped>tbody>tr:nth-of-type(odd) { background: rgba(255,255,255,0.02); }
        .table-striped>tbody>tr:nth-of-type(even) { background: rgba(255,255,255,0.05); }
        .punti-row td { background: rgba(255,215,0,0.08) !important; border-top: 2px solid #ffd700 !important; color: #ffd700; font-weight: 700; font-size: 0.9rem; }
        .punti-row:hover { background: rgba(255,215,0,0.12) !important; }
        .med { display: inline-block; width: 32px; height: 32px; line-height: 32px; border-radius: 50%; font-weight: 800; font-size: 0.85rem; text-align: center; }
        .med.gold { background: gold; color: #333; }
        .med.silver { background: silver; color: #333; }
        .med.bronze { background: #cd7f32; color: #fff; }
        .med.neutral { background: #6c757d; color: #fff; }
        .pred { display: inline-block; width: 28px; height: 28px; line-height: 28px; border-radius: 50%; font-weight: 700; font-size: 0.8rem; text-align: center; }
        .pred-correct { background: #28a745; color: #fff; box-shadow: 0 0 8px rgba(40,167,69,0.4); }
        .pred-wrong { background: #dc3545; color: #fff; }
        .pred-pending { background: #6c757d; color: #ccc; }
        .pred-empty { background: transparent; color: #555; border: 1px dashed #444; }
        .legend-box { background: rgba(255,255,255,0.04); border-radius: 10px; padding: 1rem; border: 1px solid rgba(255,255,255,0.08); }
        .legend-box p { color: #aaa; margin: 6px 0 0; font-size: 0.8rem; }
        .footer { color: #666; font-size: 0.75rem; text-align: center; padding: 1.5rem 0 0; }
        .refresh-btn { position: fixed; bottom: 20px; right: 20px; background: #e94560; color: #fff; border: none; border-radius: 50%; width: 52px; height: 52px; font-size: 22px; box-shadow: 0 4px 15px rgba(233,69,96,0.4); cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .refresh-btn:hover { transform: rotate(180deg); background: #ff6b81; }
        .update-btn { position: fixed; bottom: 20px; right: 80px; background: #28a745; color: #fff; border: none; border-radius: 50%; width: 52px; height: 52px; font-size: 22px; box-shadow: 0 4px 15px rgba(40,167,69,0.4); cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .update-btn:hover { transform: scale(1.1); background: #34ce57; }
        .update-btn:disabled { opacity: 0.5; cursor: wait; }
        .container { max-width: 100%; padding: 12px; }
        @media (max-width: 768px) {
            .card-header h1 { font-size: 1rem; }
            .table { font-size: 0.75rem; }
            .table tbody td { padding: 5px 3px; }
            .pred { width: 22px; height: 22px; line-height: 22px; font-size: 0.7rem; }
            .med { width: 26px; height: 26px; line-height: 26px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>
    <div class="container py-3">
        ' . $alertHtml . '
        <div class="card" id="card-classifica">
            <div class="card-header text-center" onclick="toggleClassifica()" style="cursor:pointer;user-select:none">
                <div class="d-flex justify-content-between align-items-center">
                    <span></span>
                    <h1 style="margin:0"><i class="fas fa-trophy"></i> TotoMondiale 2026 — Classifica e Pronostici</h1>
                    <span id="toggle-classifica" class="badge bg-secondary" style="font-size:0.8rem"><i class="fas fa-chevron-up"></i></span>
                </div>
            </div>
            <div id="body-classifica">
                <div class="card-body p-0" style="overflow-x:auto">
                    <table class="table table-striped mb-0" style="min-width:500px">
                        <thead><tr><th class="text-center" style="width:50px">#</th><th>Partecipante</th><th class="text-center">Partite</th><th class="text-center">Bonus</th><th class="text-center" style="width:80px">Totale</th><th class="text-center" style="width:100px">Vincitore</th><th class="text-center" style="width:110px">Capocannoniere</th></tr></thead>
                        <tbody>'.$leaderboardRows.'</tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="legend-box mt-3">
            <div class="row text-center align-items-center">
                <div class="col-3"><span class="badge bg-success" style="font-size:0.9rem">1</span> <span class="badge bg-danger" style="font-size:0.9rem">X</span> <span class="badge bg-secondary" style="font-size:0.9rem">2</span> <small class="text-muted ms-1">Azzeccato / Sbagliato / In attesa</small></div>
                <div class="col-3"><span class="badge bg-primary">1 pt</span> <small class="text-muted ms-1">per 1/X/2</small></div>
                <div class="col-3"><span class="badge bg-success">3 pt</span> <small class="text-muted ms-1">Vincitore</small></div>
                <div class="col-3"><span class="badge bg-warning text-dark">2 pt</span> <small class="text-muted ms-1">Capocannoniere</small></div>
            </div>
            <p class="text-center text-muted mt-2 mb-0" style="font-size:0.75rem"><i class="fas fa-info-circle"></i> Orari in fuso locale dello stadio (Canada, USA, Messico)</p>
        </div>

        '.$tabelleGironi.'

        <div class="card mt-3">
            <div class="card-header" onclick="toggleRegolamento()" style="cursor:pointer;user-select:none">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0" style="font-size:1rem;color:#fff"><i class="fas fa-book"></i> Regolamento</h2>
                    <span id="toggle-regolamento" class="badge bg-secondary" style="font-size:0.8rem"><i class="fas fa-chevron-down"></i></span>
                </div>
            </div>
            <div id="body-regolamento" class="card-body" style="display:none;font-size:0.85rem;line-height:1.6;color:#ccc;background:rgba(0,0,0,0.15)">
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

        <div class="footer">
            <i class="fas fa-sync-alt"></i> Auto-refresh ogni 30s &middot;
            <a href="#" onclick="esplodiTutto();return false" style="color:#e94560;margin-right:10px"><i class="fas fa-expand-alt"></i> Espandi tutti</a>
            <a href="#" onclick="implodiTutto();return false" style="color:#e94560"><i class="fas fa-compress-alt"></i> Comprimi tutti</a>
            &middot; Dati: <a href="https://worldcup26.ir" target="_blank" style="color:#e94560">worldcup26.ir</a>
        </div>
    </div>

    <button class="refresh-btn" onclick="location.reload()" title="Aggiorna"><i class="fas fa-sync-alt"></i></button>
    <button class="update-btn" onclick="aggiornaRisultati()" title="Aggiorna risultati da worldcup26.ir"><i class="fas fa-cloud-sun"></i></button>
    <script>
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
    function toggleGruppo(id) {
        var el = document.getElementById(id);
        var suffix = id.replace(\'gruppo_\', \'\');
        var badge = document.getElementById(\'toggle_\' + suffix);
        if (el.style.display === \'none\') {
            el.style.display = \'block\';
            badge.innerHTML = \'<i class="fas fa-chevron-up"></i>\';
        } else {
            el.style.display = \'none\';
            badge.innerHTML = \'<i class="fas fa-chevron-down"></i>\';
        }
    }
    function esplodiTutto() {
        var els = document.querySelectorAll(\'[id^="gruppo_"]\');
        for (var i = 0; i < els.length; i++) {
            els[i].style.display = \'block\';
            var g = els[i].id.replace(\'gruppo_\', \'\');
            document.getElementById(\'toggle_\' + g).innerHTML = \'<i class="fas fa-chevron-up"></i>\';
        }
    }
    function implodiTutto() {
        var els = document.querySelectorAll(\'[id^="gruppo_"]\');
        for (var i = 0; i < els.length; i++) {
            els[i].style.display = \'none\';
            var g = els[i].id.replace(\'gruppo_\', \'\');
            document.getElementById(\'toggle_\' + g).innerHTML = \'<i class="fas fa-chevron-down"></i>\';
        }
    }
    function aggiornaRisultati() {
        var btn = document.querySelector(\'.update-btn\');
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
            var vecchioBody = document.body;
            var nuovoBody = nuovo.querySelector(\'body\');
            if (nuovoBody) {
                vecchioBody.innerHTML = nuovoBody.innerHTML;
                window.scrollTo(0, pos);
            }
        }).catch(function() {
            location.reload();
        });
    }
    setTimeout(function(){ aggiornaSilenzioso(); }, 30000);
    </script>
</body>
</html>';
});

