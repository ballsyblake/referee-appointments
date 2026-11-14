<?php
// Matches (one row each, with cost) a referee worked. With `date` (a
// Friday), only that weekend; without it, all time.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$u = post('username');
$date = post('date');
if (valid_date($date)) {
    $rows = q('SELECT cost FROM appointments WHERE Date BETWEEN ? AND ? + INTERVAL 2 DAY
               AND ? IN (referee1, referee2, referee3, referee4, coach)', [$date, $date, $u]);
} else {
    $rows = q('SELECT cost FROM appointments WHERE ? IN (referee1, referee2, referee3, referee4, coach)', [$u]);
}
json_out($rows->fetchAll(PDO::FETCH_ASSOC));
