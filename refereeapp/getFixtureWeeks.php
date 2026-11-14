<?php
// Fridays of every weekend that has matches, plus the coming one, for the
// week picker on the admin schedule page.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$weeks = [];
foreach (q('SELECT DISTINCT Date FROM appointments ORDER BY Date') as $r) {
    $friday = date('Y-m-d', strtotime($r[0] . ' monday this week +4 days'));
    $weeks[$friday] = true;
}
$weeks[this_friday()] = true;
$weeks[availability_friday()] = true;
ksort($weeks);
json_out(['weeks' => array_keys($weeks), 'current' => this_friday()]);
