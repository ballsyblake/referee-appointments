<?php
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$from = post('firstDayOfWeek');
$to = post('lastDayOfWeek');
if (!valid_date($from) || !valid_date($to)) fail('Bad dates.');
json_out(q('SELECT DAYOFWEEK(date), timeStart, timeEnd, venue, venuemanagerID, shiftID, confirmed, date FROM shifts
            WHERE date BETWEEN ? AND ? ORDER BY date, venue, timeStart', [$from, $to])->fetchAll(PDO::FETCH_BOTH));
