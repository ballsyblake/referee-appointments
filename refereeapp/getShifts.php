<?php
// The logged-in venue manager's shifts between two dates.
require __DIR__ . '/lib/bootstrap.php';
$user = require_login(['venuemanager']);
$from = post('firstDayOfWeek');
$to = post('lastDayOfWeek');
if (!valid_date($from) || !valid_date($to)) fail('Bad dates.');
json_out(q("SELECT date, TIME_FORMAT(timeStart, '%l:%i %p'), TIME_FORMAT(timeEnd, '%l:%i %p'), venue, shiftID, confirmed
            FROM shifts WHERE venuemanagerID = ? AND date BETWEEN ? AND ? ORDER BY date, timeStart",
    [$user['username'], $from, $to])->fetchAll(PDO::FETCH_BOTH));
