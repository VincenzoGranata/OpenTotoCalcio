<?php

/**
 * Cron job per gestione automatica stati concorsi totocalcio
 * Da eseguire ogni 15-30 minuti via crontab
 *
 * Esempio crontab: "*/15 * * * * php /path/to/totosport/cron_totocalcio.php"
 */

include_once __DIR__.'/config.inc.php';
include_once __DIR__.'/modules/totocalcio_concorsi/auto_manage.php';

echo "[TOTOCALCIO CRON] Esecuzione ".date('Y-m-d H:i:s')."\n";
echo "[TOTOCALCIO CRON] Check automatico completato\n";
