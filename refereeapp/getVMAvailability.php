<?php
// Venue managers available for a block, as " | Name | Name".
// username is a code like "lynfriaft": venue, day (fri/sun), mor/aft.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$code = post('username');
if (!preg_match('/^(lyn|gcr|rad)(fri|sun)(mor|aft)$/', $code, $m)) text_out('');
[, $venue, $day, $part] = $m;
$date = $day === 'fri' ? availability_friday() : availability_sunday();
$hour = $part === 'mor' ? '08-9' : '16-17';
$names = q('SELECT v.FirstName, v.LastName FROM availability a JOIN venuemanagers v ON v.username = a.username
            WHERE a.date = ? AND a.venue = ? AND a.hour = ? ORDER BY v.FirstName', [$date, $venue, $hour])->fetchAll();
$out = '';
foreach ($names as $n) $out .= ' | ' . h(ucfirst($n[0]) . ' ' . ucfirst($n[1]));
text_out($out);
