<?php
$path = __DIR__ . '/../data/woocommerce_500_skus.csv';
$h = fopen($path, 'r');
$header = fgetcsv($h);
$map = array_flip($header);
$cats = [];
$concerns = [];
$names = [];
while (($row = fgetcsv($h)) !== false) {
    $step = trim((string)($row[$map['Taxonomy: routine_step']] ?? ''));
    if ($step !== '') continue;
    $cat = trim((string)($row[$map['Categories']] ?? ''));
    $cats[$cat] = ($cats[$cat] ?? 0) + 1;
    if (count($names) < 30) $names[] = $row[$map['Name']];
}
arsort($cats);
echo "Missing step categories:\n";
foreach ($cats as $c => $n) echo "  $c => $n\n";
echo "\nSample names:\n";
foreach ($names as $n) echo "  $n\n";
