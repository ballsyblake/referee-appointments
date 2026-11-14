<?php
// Email every referee appointed this fixture weekend.
require __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/mail.php';
require_admin();
$rows = q('SELECT DISTINCT r.username, r.FirstName FROM appointments a
           JOIN referees r ON r.username IN (a.referee1, a.referee2, a.referee3, a.referee4, a.coach)
           WHERE a.Date BETWEEN ? AND ?', [this_friday(), this_sunday()])->fetchAll();
$to = [];
foreach ($rows as $r) $to[$r[0]] = $r[1];
if (!$to) text_out('Nobody is appointed this weekend.');
$url = app_url();
$ok = send_mail('Your NCF appointments',
    "<h1>You have been appointed to referee</h1><p>See your matches on the <a href='" . h($url) . "'>referee portal</a>. Reply to this email if you have any issues.</p>",
    "You have been appointed to referee. See your matches at $url. Reply to this email if you have any issues.",
    $to, true);
text_out($ok ? 'Emailed ' . count($to) . ' referees.' : 'Email failed, check the mail settings.');
