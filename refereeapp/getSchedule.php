<?php
// Matches for the admin schedule page. With `friday`, only that weekend.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$fri = post('friday') ?: ($_GET['friday'] ?? '');
$sql = "SELECT Date, TIME_FORMAT(MatchTime, '%H:%i') AS MatchTime, Venue, Court, AgeGroup, appointID,
               referee1, referee2, referee3, referee4, coach, cost FROM appointments";
$params = [];
if (is_string($fri) && valid_date($fri)) {
    $sql .= ' WHERE Date BETWEEN ? AND ? + INTERVAL 2 DAY';
    $params = [$fri, $fri];
}
json_out(q($sql . ' ORDER BY Date, MatchTime, Court', $params)->fetchAll(PDO::FETCH_ASSOC));
