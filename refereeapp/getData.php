<?php
// Dashboard pie chart: matches per venue over the last 90 days and the
// next 30, in Google Charts DataTable format.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$rows = [];
foreach (q('SELECT Venue, COUNT(*) FROM appointments
            WHERE Date BETWEEN CURDATE() - INTERVAL 90 DAY AND CURDATE() + INTERVAL 30 DAY GROUP BY Venue') as $r) {
    $rows[] = ['c' => [['v' => VENUES[$r[0]] ?? $r[0]], ['v' => (int) $r[1]]]];
}
json_out([
    'cols' => [['label' => 'Venue', 'type' => 'string'], ['label' => 'Matches', 'type' => 'number']],
    'rows' => $rows,
]);
