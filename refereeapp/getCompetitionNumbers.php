<?php
// Count of nominated teams per age group, gender and division (HTML list).
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$out = '<p>';
foreach (q('SELECT age, gender, division, COUNT(*) FROM teams GROUP BY age, gender, division
            ORDER BY CAST(SUBSTR(age, 2) AS UNSIGNED), gender, division') as $r) {
    $out .= h("{$r[0]} {$r[1]} div {$r[2]}: {$r[3]}") . '<br>';
}
header('Content-Type: text/html; charset=utf-8');
echo $out . '</p>';
