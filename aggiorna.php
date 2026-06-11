<?php

require_once __DIR__ . '/config.inc.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
if (empty($aggiorna_token)) {
    $aggiorna_token = 'toto2026';
}
if ($token !== $aggiorna_token) {
    header('Location: /classifica');
    exit;
}

try {
    $pdo = new PDO("mysql:host=$db_host;port=3306;dbname=$db_name;charset=utf8mb4", $db_username, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    header('Location: /classifica?error=' . urlencode('Errore database'));
    exit;
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://worldcup26.ir/get/games',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT => 'OpenSTAManager-TotoMondiale/1.0',
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    $msg = $response === false ? $curlError : "HTTP $httpCode";
    header('Location: /classifica?error=' . urlencode("Errore API: $msg"));
    exit;
}

$games = json_decode($response, true);
$games = $games['games'] ?? [];

$updated = 0;
$updateStmt = $pdo->prepare('UPDATE tot_partite SET goal_casa = ?, goal_ospite = ?, stato = ?, minuto = ? WHERE id_sofascore = ?');

foreach ($games as $game) {
    $apiId = $game['id'] ?? null;
    if (!$apiId) continue;

    $homeScore = $game['home_score'] !== null && $game['home_score'] !== '' ? (int) $game['home_score'] : null;
    $awayScore = $game['away_score'] !== null && $game['away_score'] !== '' ? (int) $game['away_score'] : null;
    $finished = strtoupper($game['finished'] ?? 'FALSE') === 'TRUE';
    $timeElapsed = $game['time_elapsed'] ?? 'notstarted';
    $status = $finished ? 'finished' : ($timeElapsed !== 'notstarted' && $timeElapsed !== '' ? 'ongoing' : 'scheduled');
    $minuto = ($status === 'ongoing' && is_numeric($timeElapsed)) ? (int) $timeElapsed : null;

    $updateStmt->execute([$homeScore, $awayScore, $status, $minuto, $apiId]);
    if ($updateStmt->rowCount() > 0) {
        ++$updated;
    }
}

$calculated = 0;
$finishedMatches = $pdo->query("SELECT id, goal_casa, goal_ospite FROM tot_partite WHERE stato = 'finished' AND goal_casa IS NOT NULL AND goal_ospite IS NOT NULL")->fetchAll();

$predStmt = $pdo->prepare('SELECT id, pronostico FROM tot_pronostici WHERE id_partita = ?');
$updatePredStmt = $pdo->prepare('UPDATE tot_pronostici SET punti = ? WHERE id = ?');

foreach ($finishedMatches as $match) {
    $result = $match['goal_casa'] > $match['goal_ospite'] ? '1' : ($match['goal_casa'] < $match['goal_ospite'] ? '2' : 'X');

    $predStmt->execute([$match['id']]);
    $predictions = $predStmt->fetchAll();

    foreach ($predictions as $pred) {
        $punti = ($pred['pronostico'] === $result) ? 1 : 0;
        $updatePredStmt->execute([$punti, $pred['id']]);
        ++$calculated;
    }
}

header('Location: /classifica?aggiornato=1&partite=' . $updated . '&punteggi=' . $calculated);
