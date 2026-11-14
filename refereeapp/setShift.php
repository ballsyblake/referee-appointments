<?php
// Admin gives a venue manager a shift. temp[name] is the manager's full
// name, temp[day] a date, temp[venue] lyn/gcr/rad, times as HH:MM.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$t = $_POST['temp'] ?? [];
if (!is_array($t)) fail('Bad request.');
$name = trim((string) ($t['name'] ?? ''));
$date = (string) ($t['day'] ?? '');
$start = (string) ($t['timeStart'] ?? '');
$end = (string) ($t['timeEnd'] ?? '');
$venue = (string) ($t['venue'] ?? '');
if ($name === '') text_out('Empty name field, please try again.');
if (!valid_date($date) || !valid_time($start) || !valid_time($end) || !isset(VENUES[$venue])) text_out('Check the day, venue and times.');
if ($end <= $start) text_out('Finish time must be after start time.');

$like = '%' . addcslashes($name, '%_\\') . '%';
$found = q("SELECT username FROM venuemanagers WHERE password IS NOT NULL AND CONCAT(FirstName, ' ', LastName) LIKE ?", [$like])
    ->fetchAll(PDO::FETCH_COLUMN);
if (count($found) === 0) text_out("Couldn't find that venue manager, check name and try again.");
if (count($found) > 1) text_out('Too many results for that name, please try a more specific name.');

q('INSERT INTO shifts (date, timeStart, timeEnd, venue, venuemanagerID) VALUES (?, ?, ?, ?, ?)', [$date, $start, $end, $venue, $found[0]]);
text_out('Successful submission');
