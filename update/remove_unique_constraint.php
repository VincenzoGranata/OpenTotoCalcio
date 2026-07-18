<?php
/**
 * Script per rimuovere il constraint UNIQUE dalla tabella totocalcio_pronostici
 * Esegui questo script una volta per applicare la modifica al database
 */

include_once __DIR__.'/../config.inc.php';

try {
    // Usa localhost se l'host è 'db' (Docker)
    $host = ($db_host === 'db') ? 'localhost' : $db_host;
    $db = new PDO('mysql:host=' . $host . ';dbname=' . $db_name, $db_username, $db_password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verifica se il constraint esiste
    $check = $db->query("SHOW INDEX FROM totocalcio_pronostici WHERE Key_name = 'colonna_partita'");
    if ($check->rowCount() > 0) {
        // Rimuove il constraint
        $db->query("ALTER TABLE `totocalcio_pronostici` DROP INDEX `colonna_partita`");
        echo "✓ Constraint UNIQUE rimosso con successo!\n";
        echo "✓ Ora è possibile inserire sia il pronostico 1X2 che il risultato esatto per la stessa partita.\n";
    } else {
        echo "✓ Il constraint UNIQUE non esiste o è già stato rimosso.\n";
    }

} catch (PDOException $e) {
    echo "✗ Errore: " . $e->getMessage() . "\n";
    exit(1);
}
