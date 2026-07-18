<?php

/**
 * Script per sincronizzare gli orari delle partite con Fotmob API
 * Aggiorna data_partita di tutte le partite con gli orari reali
 */

include_once __DIR__.'/../../core.php';

use TotoCalcio\FotmobService;

try {
    echo "⏳ Sincronizzazione orari partite in corso...\n";

    $service = new FotmobService();
    $result = $service->syncAll();

    echo "✅ Sincronizzazione completata!\n";
    echo "📝 Partite aggiornate/sincronizzate: $result\n";

    // Verifica gli orari aggiornati
    $partite = $dbo->fetchArray('
        SELECT id_concorso, squadra_casa, squadra_ospite, data_partita, stato
        FROM totocalcio_partite
        ORDER BY id_concorso, ordine
        LIMIT 20
    ');

    echo "\n📋 Prime 20 partite:\n";
    echo "Giornata | Casa | Ospite | Orario | Stato\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($partite as $p) {
        echo $p['id_concorso'] . " | " . substr($p['squadra_casa'], 0, 15) . " | " . substr($p['squadra_ospite'], 0, 15) . " | " . ($p['data_partita'] ?? 'N/A') . " | " . $p['stato'] . "\n";
    }

} catch (Exception $e) {
    echo "❌ Errore durante la sincronizzazione: " . $e->getMessage() . "\n";
    exit(1);
}
