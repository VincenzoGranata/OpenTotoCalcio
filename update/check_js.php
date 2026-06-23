<?php
$js = file_get_contents('/var/www/html/assets/dist/js/app.min.js');
$pos = 0;
$count = 0;
while (($pos = strpos($js, 'editor.php', $pos)) !== false && $count < 5) {
    $start = max(0, $pos - 50);
    $end = min(strlen($js), $pos + 80);
    echo substr($js, $start, $end - $start) . "\n---\n";
    $pos += 10;
    $count++;
}
