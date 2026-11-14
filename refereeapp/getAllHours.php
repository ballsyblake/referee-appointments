<?php
// Total cost since dateRange. username = 'ref' (referees only),
// 'vm' (venue managers only) or '' (both).
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$since = post('dateRange');
if (!valid_date($since)) $since = '1970-01-01';
$which = post('username');

$ref = 0.0;
if ($which !== 'vm') {
    // Each filled referee/coach slot is paid the match cost.
    $ref = (float) q("SELECT COALESCE(SUM(cost * ((referee1 IS NOT NULL) + (referee2 IS NOT NULL)
                       + (referee3 IS NOT NULL) + (referee4 IS NOT NULL) + (coach IS NOT NULL))), 0)
                      FROM appointments WHERE Date >= ?", [$since])->fetchColumn();
}
$vm = 0.0;
if ($which !== 'ref') {
    $vm = (float) q('SELECT COALESCE(SUM(s.hours * v.payRate), 0) FROM shifts s
                     JOIN venuemanagers v ON v.username = s.venuemanagerID WHERE s.date >= ?', [$since])->fetchColumn();
}
text_out('$' . number_format($ref + $vm, 2));
