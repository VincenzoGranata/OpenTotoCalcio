<?php
require __DIR__ . '/config.inc.php';

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

function formatStatoCalcio($stato, $minuto = null) {
    if ($stato === 'scheduled') return 'Programmata';
    if ($stato === 'ongoing') return $minuto ? $minuto . '\'' : 'In corso';
    if ($stato === 'finished') return 'Terminata';
    return $stato;
}

// ─────────────────────────────────────────────
// DATA FETCHING
// ─────────────────────────────────────────────

// 1. Partecipanti + classifica generale
$partecipanti = $pdo->query('
    SELECT p.id, p.nome,
        COALESCE(SUM(c.punti_totali), 0) AS totale
    FROM totocalcio_partecipanti p
    LEFT JOIN totocalcio_colonne c ON c.id_partecipante = p.id
    GROUP BY p.id, p.nome
    ORDER BY totale DESC, p.nome ASC
')->fetchAll();

$numP = count($partecipanti);

// 2. Mini classifiche
$miniClassifiche = $pdo->query('
    SELECT * FROM totocalcio_mini_classifiche
    ORDER BY data_inizio DESC
')->fetchAll();

// Classifica per ogni mini classifica
$miniRankings = [];
foreach ($miniClassifiche as $mc) {
    $stmt = $pdo->prepare('
        SELECT p.id, p.nome,
            COALESCE(SUM(c.punti_totali), 0) AS totale
        FROM totocalcio_partecipanti p
        LEFT JOIN totocalcio_colonne c ON c.id_partecipante = p.id
        LEFT JOIN totocalcio_concorsi co ON co.id = c.id_concorso
        WHERE co.data_concorso >= ?
        AND (? IS NULL OR co.data_concorso <= ?)
        GROUP BY p.id, p.nome
        ORDER BY totale DESC, p.nome ASC
    ');
    $stmt->execute([$mc->data_inizio, $mc->data_fine, $mc->data_fine]);
    $miniRankings[$mc->id] = $stmt->fetchAll();
}

// Premi per mini classifica
$premiByMini = [];
foreach ($miniClassifiche as $mc) {
    $stmt = $pdo->prepare('SELECT * FROM totocalcio_mini_classifiche_premi WHERE id_mini_classifica = ? ORDER BY posizione');
    $stmt->execute([$mc->id]);
    $premiByMini[$mc->id] = $stmt->fetchAll();
}

// Vincite
$vincite = $pdo->query('
    SELECT * FROM totocalcio_vincite ORDER BY id_mini_classifica, posizione
')->fetchAll();
$vinciteMap = [];
foreach ($vincite as $v) {
    $vinciteMap[$v->id_mini_classifica][$v->id_partecipante] = $v;
}

// 3. Concorsi (ordinati per giornata)
$concorsi = $pdo->query('
    SELECT co.*,
        (SELECT MIN(p.data_partita) FROM totocalcio_partite p WHERE p.id_concorso = co.id) AS data_concorso,
        (SELECT COUNT(*) FROM totocalcio_colonne WHERE id_concorso = co.id) AS num_colonne
    FROM totocalcio_concorsi co
    ORDER BY co.giornata DESC
')->fetchAll();

// 4. Partite per ogni concorso
$partiteByConcorso = [];
$allMatchIds = [];
foreach ($concorsi as $co) {
    $stmt = $pdo->prepare('
        SELECT * FROM totocalcio_partite WHERE id_concorso = ? ORDER BY pannello, ordine
    ');
    $stmt->execute([$co->id]);
    $matches = $stmt->fetchAll();
    $partiteByConcorso[$co->id] = $matches;
    foreach ($matches as $m) {
        $allMatchIds[] = $m->id;
    }
}

// 5. Pronostici
$predMap = [];
if (!empty($allMatchIds)) {
    $chunks = array_chunk($allMatchIds, 100);
    foreach ($chunks as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $pdo->prepare("
            SELECT pr.*, c.id_partecipante
            FROM totocalcio_pronostici pr
            JOIN totocalcio_colonne c ON c.id = pr.id_colonna
            WHERE pr.id_partita IN ($placeholders)
        ");
        $stmt->execute($chunk);
        $rows = $stmt->fetchAll();
        foreach ($rows as $pr) {
            $predMap[$pr->id_partita][$pr->id_partecipante][] = $pr;
        }
    }
}

// 6. Marcatori reali per partita
$marcatoriReali = [];
if (!empty($allMatchIds)) {
    $chunks = array_chunk($allMatchIds, 100);
    foreach ($chunks as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $stmt = $pdo->prepare("
            SELECT mp.*, g.nome AS nome_giocatore
            FROM totocalcio_marcatori_partita mp
            JOIN totocalcio_giocatori g ON g.id = mp.id_giocatore
            WHERE mp.id_partita IN ($placeholders)
        ");
        $stmt->execute($chunk);
        $rows = $stmt->fetchAll();
        foreach ($rows as $mp) {
            $marcatoriReali[$mp->id_partita][] = $mp;
        }
    }
}

// 7. Quote stagionali (per mostrare stato pagamento)
$quote = $pdo->query('
    SELECT q.*, p.nome FROM totocalcio_quote_stagionali q
    JOIN totocalcio_partecipanti p ON p.id = q.id_partecipante
    ORDER BY p.nome
')->fetchAll();
$quoteMap = [];
foreach ($quote as $q) {
    $quoteMap[$q->id_partecipante] = $q;
}

// ─────────────────────────────────────────────
// HELPER: calcola se un pronostico è corretto
// ─────────────────────────────────────────────
function checkPronostico($pr, $match, $marcatoriReali) {
    if ($match->stato !== 'finished') return null;
    if ($pr->tipo === '1x2') {
        $gc = $match->goal_casa;
        $go = $match->goal_ospite;
        if ($gc === null || $go === null) return null;
        $result = $gc > $go ? '1' : ($gc < $go ? '2' : 'X');
        return $pr->pronostico === $result;
    }
    if ($pr->tipo === 'risultato_esatto') {
        $gc = $match->goal_casa;
        $go = $match->goal_ospite;
        if ($gc === null || $go === null) return null;
        $expected = $gc . '-' . $go;
        return $pr->pronostico === $expected;
    }
    if ($pr->tipo === 'marcatore') {
        $marcatori = $marcatoriReali[$match->id] ?? [];
        foreach ($marcatori as $m) {
            if ((int)$m->id_giocatore === (int)$pr->pronostico) return true;
        }
        return false;
    }
    return null;
}

// ─────────────────────────────────────────────
// RENDER CLASSIFICA GENERALE
// ─────────────────────────────────────────────
$leaderboardHtml = '';
$pos = 1;
foreach ($partecipanti as $p) {
    $med = '';
    if ($pos === 1) $med = '<span class="med gold">1</span>';
    elseif ($pos === 2) $med = '<span class="med silver">2</span>';
    elseif ($pos === 3) $med = '<span class="med bronze">3</span>';
    else $med = '<span class="med neutral">'.$pos.'</span>';

    $quota = $quoteMap[$p->id] ?? null;
    $pagatoBadge = $quota && $quota->pagato
        ? '<span class="badge bg-success" style="font-size:0.6rem">Pagato</span>'
        : '<span class="badge bg-secondary" style="font-size:0.6rem">Non pagato</span>';

    $leaderboardHtml .= "<tr>
        <td class=\"tc\">$med</td>
        <td><strong>" . e($p->nome) . "</strong> $pagatoBadge</td>
        <td class=\"tc\"><strong style=\"font-size:20px\">" . (int)$p->totale . "</strong></td>
    </tr>\n";
    ++$pos;
}

// ─────────────────────────────────────────────
// RENDER MINI CLASSIFICHE
// ─────────────────────────────────────────────
$miniHtml = '';
foreach ($miniClassifiche as $mc) {
    $ranking = $miniRankings[$mc->id] ?? [];
    $premi = $premiByMini[$mc->id] ?? [];
    $statoBadge = $mc->stato === 'attiva'
        ? '<span class="badge bg-success">Attiva</span>'
        : '<span class="badge bg-secondary">Conclusa</span>';

    $rows = '';
    $pos = 1;
    foreach ($ranking as $rp) {
        $med = '';
        if ($pos === 1) $med = '<span class="med gold">1</span>';
        elseif ($pos === 2) $med = '<span class="med silver">2</span>';
        elseif ($pos === 3) $med = '<span class="med bronze">3</span>';
        else $med = '<span class="med neutral">'.$pos.'</span>';

        $premio = '';
        foreach ($premi as $pr) {
            if ((int)$pr->posizione === $pos) {
                $premio = number_format($pr->importo, 2, ',', '.') . '€';
                break;
            }
        }

        $v = $vinciteMap[$mc->id][$rp->id] ?? null;
        $pagato = $v && $v->pagato
            ? '<span class="badge bg-success">Pagato</span>'
            : ($v ? '<span class="badge bg-warning text-dark">In attesa</span>' : '');

        $rows .= "<tr>
            <td class=\"tc\">$med</td>
            <td>" . e($rp->nome) . "</td>
            <td class=\"tc\"><strong>" . (int)$rp->totale . "</strong></td>
            <td class=\"tc\">$premio</td>
            <td class=\"tc\">$pagato</td>
        </tr>\n";
        ++$pos;
    }

    if (empty($rows)) {
        $rows = '<tr><td colspan="5" class="text-center text-muted">Nessun dato</td></tr>';
    }

    $dateRange = date('d/m/Y', strtotime($mc->data_inizio));
    if ($mc->data_fine) $dateRange .= ' - ' . date('d/m/Y', strtotime($mc->data_fine));
    else $dateRange .= ' - in corso';

    $miniHtml .= '
    <div class="card mt-3">
        <div class="card-header" onclick="toggleSection(\'mini_' . $mc->id . '\')" style="cursor:pointer;user-select:none">
            <div class="d-flex justify-content-between align-items-center">
                <h2 style="margin:0;font-size:1rem"><i class="fas fa-medal"></i> ' . e($mc->nome) . ' <small class="text-muted">(' . $dateRange . ')</small> ' . $statoBadge . '</h2>
                <span id="tog_mini_' . $mc->id . '" class="badge bg-secondary"><i class="fas fa-chevron-up"></i></span>
            </div>
        </div>
        <div id="sec_mini_' . $mc->id . '" class="card-body p-0" style="overflow-x:auto">
            <table class="table table-striped mb-0" style="min-width:450px">
                <thead><tr>
                    <th class="tc" style="width:50px">#</th>
                    <th>Partecipante</th>
                    <th class="tc">Punti</th>
                    <th class="tc">Premio</th>
                    <th class="tc">Stato</th>
                </tr></thead>
                <tbody>' . $rows . '</tbody>
            </table>
        </div>
    </div>';
}

// ─────────────────────────────────────────────
// RENDER CONCORSI (grid pronostici)
// ─────────────────────────────────────────────
$colW = $numP > 20 ? 55 : 65;

function renderConcorsoGrid($co, $partite, $partecipanti, $predMap, $marcatoriReali, $numP, $colW) {
    $statoBadge = '';
    if ($co->stato === 'aperto') $statoBadge = '<span class="badge bg-success">Aperto</span>';
    elseif ($co->stato === 'chiuso') $statoBadge = '<span class="badge bg-warning text-dark">Chiuso</span>';
    elseif ($co->stato === 'concluso') $statoBadge = '<span class="badge bg-secondary">Concluso</span>';

    $chiusura = $co->data_chiusura ? date('d/m H:i', strtotime($co->data_chiusura)) : '';

    $html = '
    <div class="card mt-3">
        <div class="card-header" onclick="toggleSection(\'co_' . $co->id . '\')" style="cursor:pointer;user-select:none">
            <div class="d-flex justify-content-between align-items-center">
                <h2 style="margin:0;font-size:1rem"><i class="fas fa-calendar-alt"></i> ' . e($co->nome) . ' ' . $statoBadge . ' <small class="text-muted">' . $chiusura . '</small> <span class="badge bg-info" style="font-size:0.6rem">' . (int)$co->num_colonne . ' colonne</span></h2>
                <span id="tog_co_' . $co->id . '" class="badge bg-secondary"><i class="fas fa-chevron-up"></i></span>
            </div>
        </div>
        <div id="sec_co_' . $co->id . '" class="card-body p-0" style="overflow-x:auto">
            <table class="table table-bordered mb-0" style="min-width:' . (380 + $numP * $colW) . 'px;font-size:0.78rem">';

    // HEADER
    $html .= '<thead><tr>
        <th style="width:30px" class="tc">#</th>
        <th style="width:90px">Tipo</th>
        <th style="width:160px">Partita</th>
        <th style="width:65px" class="tc">Ris</th>';
    foreach ($partecipanti as $p) {
        $html .= '<th class="tc" style="width:' . $colW . 'px;writing-mode:vertical-lr;height:90px;vertical-align:bottom;font-size:0.6rem;padding:3px 1px;overflow:hidden" title="' . e($p->nome) . '">' . e($p->nome) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    $puntiConcorso = array_fill(0, $numP, 0);

    foreach ($partite as $m) {
        $gc = $m->goal_casa !== null ? (int)$m->goal_casa : null;
        $go = $m->goal_ospite !== null ? (int)$m->goal_ospite : null;
        $ris = ($gc !== null && $go !== null) ? "$gc-$go" : '?';
        $fin = $m->stato === 'finished';
        $live = $m->stato === 'ongoing';

        $sb = '';
        if ($fin) $sb = '<span class="badge bg-success">T</span>';
        elseif ($live) $sb = '<span class="badge bg-warning text-dark">' . ($m->minuto ? $m->minuto . "'" : 'Corso') . '</span>';
        else $sb = '<span class="badge bg-secondary">' . ($m->data_partita ? date('d/m H:i', strtotime($m->data_partita)) : '') . '</span>';

        $tipoLabel = $m->pannello === 'obbligatorio' ? 'OB' : 'OP';
        $tipoClass = $m->pannello === 'obbligatorio' ? 'text-primary' : 'text-warning';

        $html .= '<tr>
            <td class="tc">' . (int)$m->ordine . '</td>
            <td class="tc"><small class="' . $tipoClass . ' fw-bold">' . $tipoLabel . '</small></td>
            <td><small>' . e($m->squadra_casa) . '</small><br><small>' . e($m->squadra_ospite) . '</small></td>
            <td class="tc">' . $sb . '<br><strong>' . $ris . '</strong></td>';

        foreach ($partecipanti as $idx => $p) {
            $cell = '';
            $prs = $predMap[$m->id][$p->id] ?? [];
            $pr = $prs[0] ?? null;

            if ($pr) {
                $correct = checkPronostico($pr, $m, $marcatoriReali);
                $label = $pr->pronostico;

                if ($pr->tipo === 'risultato_esatto') {
                    $label = str_replace('-', '-', $pr->pronostico);
                } elseif ($pr->tipo === 'marcatore') {
                    $label = '⚽';
                }

                if ($fin) {
                    if ($correct === true) {
                        $cell = '<span class="pred pred-correct" title="Corretto">' . $label . '</span>';
                        $puntiConcorso[$idx] += (int)$pr->punti;
                    } elseif ($correct === false) {
                        $cell = '<span class="pred pred-wrong" title="Errato">' . $label . '</span>';
                    } else {
                        $cell = '<span class="pred pred-pending" title="In attesa">' . $label . '</span>';
                    }
                } else {
                    $cell = '<span class="pred pred-pending">' . $label . '</span>';
                }

                // tooltip con dettagli
                $tooltipParts = [];
                $tooltipParts[] = 'Tipo: ' . $pr->tipo;
                $tooltipParts[] = 'Pronostico: ' . $pr->pronostico;
                $tooltipParts[] = 'Punti: ' . (int)$pr->punti;
                $cell = '<span title="' . e(implode(' | ', $tooltipParts)) . '">' . $cell . '</span>';
            } else {
                $cell = '<span class="pred pred-empty">-</span>';
            }

            $html .= '<td class="tc">' . $cell . '</td>';
        }

        $html .= "</tr>\n";
    }

    // Riga punti concorso
    $html .= '<tr class="punti-row">
        <td colspan="4" class="text-end fw-bold"><i class="fas fa-star"></i> Punti</td>';
    foreach ($partecipanti as $idx => $p) {
        $html .= '<td class="tc fw-bold" style="font-size:1rem;color:#ffd700">' . $puntiConcorso[$idx] . '</td>';
    }
    $html .= "</tr>\n";

    $html .= '</tbody></table></div></div>';
    return $html;
}

$concorsoCorrenteHtml = '';
$concorsiPrecedentiHtml = '';

$first = true;
foreach ($concorsi as $co) {
    $partite = $partiteByConcorso[$co->id] ?? [];
    if (empty($partite)) continue;

    $grid = renderConcorsoGrid($co, $partite, $partecipanti, $predMap, $marcatoriReali, $numP, $colW);

    if ($first && $co->stato !== 'concluso') {
        $concorsoCorrenteHtml = $grid;
        $first = false;
    } else {
        $concorsiPrecedentiHtml .= $grid;
    }
}

// Se nessun concorso aperto, il primo diventa "corrente"
if (empty($concorsoCorrenteHtml) && !empty($concorsiPrecedentiHtml)) {
    // sposta il primo dei precedenti come corrente
    $pos = strpos($concorsiPrecedentiHtml, '<div class="card mt-3">');
    if ($pos !== false) {
        $concorsoCorrenteHtml = substr($concorsiPrecedentiHtml, $pos);
        $endPos = strpos($concorsiPrecedentiHtml, '<div class="card mt-3">', $pos + 1);
        if ($endPos !== false) {
            $concorsoCorrenteHtml = substr($concorsiPrecedentiHtml, $pos, $endPos - $pos);
            $concorsiPrecedentiHtml = substr($concorsiPrecedentiHtml, 0, $pos) . substr($concorsiPrecedentiHtml, $endPos);
        } else {
            $concorsoCorrenteHtml = $concorsiPrecedentiHtml;
            $concorsiPrecedentiHtml = '';
        }
    }
}

// ─────────────────────────────────────────────
// OUTPUT HTML
// ─────────────────────────────────────────────
$alertHtml = '';
if (!empty($_GET['aggiornato'])) {
    $punti = (int)($_GET['punti'] ?? 0);
    $alertHtml = '<div class="alert alert-success alert-dismissible fade show" role="alert" style="font-size:0.85rem;margin-bottom:1rem">
        <i class="fas fa-check-circle"></i> Punti ricalcolati per <strong>' . $punti . '</strong> pronostici.
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
<meta name="viewport" content="initial-scale=1.0,width=device-width">
<title>Totocalcio Serie A</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body{background:#f5f6fa;min-height:100vh;padding-bottom:80px;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif}
.card{border:none;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);background:#fff}
.card-header{background:#fff;border-bottom:2px solid #e0e0e0;padding:.8rem 1.2rem}
.card-header h1{color:#333;font-weight:700;margin:0;font-size:1.2rem}
.card-header h2{color:#333;font-weight:700;margin:0;font-size:1.1rem}
.card-header i{color:#e94560}
.card-header h1 i{color:#ffd700}
.table{margin-bottom:0;font-size:.82rem}
.table thead th{background:#fafafa;color:#e94560;border-bottom:2px solid #e0e0e0;font-weight:700;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;padding:8px 4px;white-space:nowrap}
.table tbody td{padding:6px 4px;vertical-align:middle;color:#444;border-color:#eee;font-size:.8rem}
.table tbody tr:hover{background:#f0f0ff!important}
.table-striped>tbody>tr:nth-of-type(odd){background:#fff}
.table-striped>tbody>tr:nth-of-type(even){background:#fafbfc}
.table-bordered>tbody>tr:nth-of-type(odd){background:#fff}
.table-bordered>tbody>tr:nth-of-type(even){background:#f8f9ff}
.punti-row td{background:#fff8e1!important;border-top:2px solid #ffc107!important;color:#e65100;font-weight:700}
.punti-row:hover{background:#fff3cd!important}
.med{display:inline-block;width:30px;height:30px;line-height:30px;border-radius:50%;font-weight:800;font-size:.82rem;text-align:center}
.med.gold{background:gold;color:#333}
.med.silver{background:silver;color:#333}
.med.bronze{background:#cd7f32;color:#fff}
.med.neutral{background:#e0e0e0;color:#666}
.pred{display:inline-block;min-width:26px;height:26px;line-height:26px;border-radius:50%;font-weight:700;font-size:.7rem;text-align:center;padding:0 3px}
.pred-correct{background:#4caf50;color:#fff}
.pred-wrong{background:#ef5350;color:#fff}
.pred-pending{background:#bdbdbd;color:#fff}
.pred-empty{background:transparent;color:#bbb;border:1px dashed #ccc}
.lg{background:#fff;border-radius:10px;padding:.8rem;border:1px solid #e0e0e0}
.lg p{color:#666;margin:4px 0 0;font-size:.78rem}
.ft{color:#999;font-size:.72rem;text-align:center;padding:1.2rem 0 0}
.tc{text-align:center}
.fw-bold{font-weight:700}
.mb0{margin-bottom:0}
.p0{padding:0}
.mt3{margin-top:1rem}
.container{max-width:100%;padding:10px}
.section-highlight{border-left:4px solid #e94560!important}
@media(max-width:768px){
.table{font-size:.7rem}.table tbody td{padding:3px 2px}.pred{min-width:20px;height:20px;line-height:20px;font-size:.65rem}.med{width:24px;height:24px;line-height:24px;font-size:.7rem}.card-header h2{font-size:.95rem}
}
</style>
</head>
<body>
<div class="container py-3">
' . $alertHtml . '

<!-- INTESTAZIONE -->
<div class="card">
    <div class="card-header text-center" style="border-bottom-color:#e94560">
        <h1><i class="fas fa-trophy"></i> Totocalcio Serie A</h1>
        <p class="text-muted mb-0" style="font-size:0.8rem">Pronostici e classifiche — Stagione ' . date('Y') . '/' . (date('Y') + 1) . '</p>
    </div>
</div>

<!-- CLASSIFICA GENERALE -->
<div class="card mt-3 section-highlight">
    <div class="card-header" onclick="toggleSection(\'classifica-gen\')" style="cursor:pointer;user-select:none">
        <div class="d-flex justify-content-between align-items-center">
            <h2 style="margin:0"><i class="fas fa-list"></i> Classifica Generale</h2>
            <span id="tog_classifica-gen" class="badge bg-secondary"><i class="fas fa-chevron-up"></i></span>
        </div>
    </div>
    <div id="sec_classifica-gen" class="card-body p-0" style="overflow-x:auto">
        <table class="table table-striped mb-0" style="min-width:350px">
            <thead><tr>
                <th class="tc" style="width:50px">#</th>
                <th>Partecipante</th>
                <th class="tc" style="width:80px">Totale</th>
            </tr></thead>
            <tbody>' . $leaderboardHtml . '</tbody>
        </table>
    </div>
</div>

<!-- LEGENDA -->
<div class="lg mt3">
    <div class="row text-center align-items-center g-2">
        <div class="col-3">
            <span class="pred pred-correct" style="display:inline-block">1</span>
            <span class="pred pred-wrong" style="display:inline-block">X</span>
            <span class="pred pred-pending" style="display:inline-block">2</span>
            <small class="text-muted ms-1">Esito</small>
        </div>
        <div class="col-3">
            <span class="badge bg-primary">1 pt</span>
            <small class="text-muted ms-1">1/X/2</small>
        </div>
        <div class="col-3">
            <span class="badge bg-success">3 pt</span>
            <small class="text-muted ms-1">Ris. esatto</small>
        </div>
        <div class="col-3">
            <span class="badge bg-warning text-dark">2 pt</span>
            <small class="text-muted ms-1">Marcatore</small>
        </div>
    </div>
    <p class="text-center mt-2 mb-0" style="font-size:0.75rem;color:#999">
        <i class="fas fa-circle text-success"></i> Corretto &middot;
        <i class="fas fa-circle text-danger"></i> Errato &middot;
        <i class="fas fa-circle text-secondary"></i> In attesa &middot;
        OB = Obbligatorio &middot; OP = Opzionale
    </p>
</div>

<!-- MINI CLASSIFICHE -->
' . $miniHtml . '

<!-- CONCORSO CORRENTE -->
' . $concorsoCorrenteHtml . '

<!-- CONCORSI PRECEDENTI -->
' . (!empty($concorsiPrecedentiHtml) ? '
<div class="card mt-3">
    <div class="card-header" onclick="toggleSection(\'precedenti\')" style="cursor:pointer;user-select:none">
        <div class="d-flex justify-content-between align-items-center">
            <h2 style="margin:0;font-size:1rem"><i class="fas fa-history"></i> Concorsi Precedenti</h2>
            <span id="tog_precedenti" class="badge bg-secondary"><i class="fas fa-chevron-down"></i></span>
        </div>
    </div>
    <div id="sec_precedenti" class="card-body p-0" style="display:none">
        ' . $concorsiPrecedentiHtml . '
    </div>
</div>' : '') .

'<!-- FOOTER -->
<div class="ft">
    <i class="fas fa-sync-alt"></i> Auto-refresh 30s &middot;
    <a href="#" onclick="expandAll();return false" style="color:#e94560;margin-right:10px"><i class="fas fa-expand-alt"></i> Espandi tutti</a>
    <a href="#" onclick="collapseAll();return false" style="color:#e94560"><i class="fas fa-compress-alt"></i> Comprimi tutti</a>
</div>
</div>

<script>
function toggleSection(id) {
    var el = document.getElementById("sec_" + id);
    var badge = document.getElementById("tog_" + id);
    if (!el || !badge) return;
    if (el.style.display === "none") {
        el.style.display = "block";
        badge.innerHTML = "<i class=\"fas fa-chevron-up\"></i>";
    } else {
        el.style.display = "none";
        badge.innerHTML = "<i class=\"fas fa-chevron-down\"></i>";
    }
}
function expandAll() {
    var els = document.querySelectorAll("[id^=sec_]");
    for (var i = 0; i < els.length; i++) {
        els[i].style.display = "block";
        var id = els[i].id.replace("sec_", "");
        var badge = document.getElementById("tog_" + id);
        if (badge) badge.innerHTML = "<i class=\"fas fa-chevron-up\"></i>";
    }
}
function collapseAll() {
    var els = document.querySelectorAll("[id^=sec_]");
    for (var i = 0; i < els.length; i++) {
        els[i].style.display = "none";
        var id = els[i].id.replace("sec_", "");
        var badge = document.getElementById("tog_" + id);
        if (badge) badge.innerHTML = "<i class=\"fas fa-chevron-down\"></i>";
    }
}
// Auto-refresh silenzioso
function silentRefresh() {
    var pos = window.scrollY;
    fetch(window.location.href).then(function(r) { return r.text(); }).then(function(html) {
        var d = document.createElement("div");
        d.innerHTML = html;
        var nb = d.querySelector("body");
        if (nb) {
            document.body.innerHTML = nb.innerHTML;
            window.scrollTo(0, pos);
        }
    }).catch(function() { location.reload(); });
}
setTimeout(function(){ silentRefresh(); }, 30000);
</script>
</body>
</html>';
