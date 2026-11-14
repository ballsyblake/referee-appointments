<?php
// Email every venue manager with a shift this fixture weekend.
require __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/mail.php';
require_admin();
$rows = q('SELECT DISTINCT v.username, v.FirstName FROM shifts s JOIN venuemanagers v ON v.username = s.venuemanagerID
           WHERE s.date BETWEEN ? AND ?', [this_friday(), this_sunday()])->fetchAll();
$to = [];
foreach ($rows as $r) $to[$r[0]] = $r[1];
if (!$to) text_out('No shifts this weekend.');
$url = app_url();
$ok = send_mail('Your NCF shifts',
    "<h1>You have shifts at NCF this weekend</h1><p>See and confirm them on the <a href='" . h($url) . "'>venue manager portal</a>. Reply to this email if you have any issues.</p>",
    "You have shifts at NCF this weekend. See and confirm them at $url.",
    $to, true);
text_out($ok ? 'Emailed ' . count($to) . ' venue managers.' : 'Email failed, check the mail settings.');
