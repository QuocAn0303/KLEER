<?php
$path = __DIR__ . '/../data/woocommerce_500_skus.csv';
$h = fopen($path, 'r');
$header = fgetcsv($h);
$map = array_flip($header);
$stats = [];
$rows = 0;
$empty = [];
while (($row = fgetcsv($h)) !== false) {
    if (!$row || !isset($row[$map['SKU']])) continue;
    $rows++;
    foreach (['Taxonomy: routine_step', 'Taxonomy: skin_type', 'Taxonomy: skin_concern', 'Attribute 1 value(s)'] as $col) {
        $v = trim((string) ($row[$map[$col]] ?? ''));
        $stats[$col][$v === '' ? '(EMPTY)' : $v] = ($stats[$col][$v === '' ? '(EMPTY)' : $v] ?? 0) + 1;
    }
}
fclose($h);
echo 'rows=' . $rows . PHP_EOL;
foreach ($stats as $col => $vals) {
    arsort($vals);
    echo "\n== $col ==\n";
    $i = 0;
    foreach ($vals as $v => $c) {
        echo "  $v => $c\n";
        if (++$i >= 30) { echo "  ...\n"; break; }
    }
}

// sample routine step data rows
$h = fopen($path, 'r');
$header = fgetcsv($h);
$map = array_flip($header);
$shown = 0;
while (($row = fgetcsv($h)) !== false) {
    $step = trim((string)($row[$map['Taxonomy: routine_step']] ?? ''));
    $concern = trim((string)($row[$map['Taxonomy: skin_concern']] ?? ''));
    if ($step !== '' && $shown < 8) {
        echo "STEP[$step] concern[$concern] name=" . $row[$map['Name']] . PHP_EOL;
        $shown++;
    }
}
