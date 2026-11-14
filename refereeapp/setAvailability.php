<?php
// The logged-in referee or venue manager submits availability for the next
// fixture weekend. checkboxarray[] holds slot codes like "frilyn16-17":
// 3-letter day, 3-letter venue, then the hour slot.
require __DIR__ . '/lib/bootstrap.php';
$user = require_login(['referee', 'venuemanager']);
$username = $user['username'];

const HOURS = ['08-9', '10-11', '12-13', '14-15', '16-17', '18-19', '20-21'];
// Venue manager checkboxes stand for a whole block of hour slots.
const VM_BLOCKS = [
    'frilyn16-17' => ['16-17', '18-19', '20-21'],
    'sunlyn08-9'  => ['08-9', '10-11', '12-13'],
    'sunlyn14-15' => ['14-15', '16-17', '18-19'],
    'sungcr08-9'  => ['08-9', '10-11', '12-13'],
    'sungcr12-13' => ['12-13', '14-15', '16-17'],
    'sunrad08-9'  => ['08-9', '10-11', '12-13'],
    'sunrad12-13' => ['12-13', '14-15', '16-17'],
];

$fri = availability_friday();
$sun = availability_sunday();
$rows = [];
$slots = $_POST['checkboxarray'] ?? [];
if (!is_array($slots)) $slots = [];

foreach ($slots as $code) {
    if (!is_string($code) || !preg_match('/^(fri|sun)(lyn|gcr|rad)(.+)$/', $code, $m)) continue;
    [, $day, $venue, $hour] = $m;
    $date = $day === 'fri' ? $fri : $sun;
    if ($user['type'] === 'venuemanager') {
        foreach (VM_BLOCKS[$code] ?? [] as $h) $rows[] = [$date, $h, $venue];
    } elseif (in_array($hour, HOURS, true)) {
        $rows[] = [$date, $hour, $venue];
        // The last Sunday Lyneham slot runs 4-7pm, so it also covers 18-19.
        if ($day === 'sun' && $venue === 'lyn' && $hour === '16-17') $rows[] = [$date, '18-19', $venue];
    }
}

$pdo = db();
$pdo->beginTransaction();
// Replace this user's availability for that weekend only; older weeks stay
// as a record.
q('DELETE FROM availability WHERE username = ? AND date IN (?, ?)', [$username, $fri, $sun]);
$insert = $pdo->prepare('INSERT INTO availability (username, date, hour, venue) VALUES (?, ?, ?, ?)');
foreach (array_unique($rows, SORT_REGULAR) as [$date, $hour, $venue]) {
    $insert->execute([$username, $date, $hour, $venue]);
}
$comments = clean_text(post('comments'), 1000);
if ($comments !== '') {
    $table = $user['type'] === 'referee' ? 'referees' : 'venuemanagers';
    q("UPDATE $table SET comments = ?, commentDate = CURDATE() WHERE username = ?", [$comments, $username]);
}
$pdo->commit();
json_out(['success' => true, 'saved' => count($rows), 'friday' => $fri, 'sunday' => $sun]);
