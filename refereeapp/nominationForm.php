<?php
// Public team nomination form: one team, its staff and up to 15 players.
require __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/mail.php';
public_endpoint();

$team = clean_text(post('nameTeam'), 150);
$age = post('ageGroup');
$gender = post('gender');
$division = post('division');
if ($team === '') fail('Please enter a team name.');
if (!preg_match('/^U\d{1,2}$/', $age)) fail('Please choose an age group.');
if (!in_array($gender, ['open', 'girls'], true)) fail('Please choose a gender.');
if (!in_array($division, ['1', '2'], true)) fail('Please choose a division.');

$players = [];
for ($i = 1; $i <= 15; $i++) {
    $first = clean_text(post("firstName$i"), 100);
    $last = clean_text(post("lastName$i"), 100);
    if ($first === '' && $last === '') continue;
    $dob = post("dob$i");
    if ($dob !== '' && !valid_date($dob)) fail("Player $i: date of birth is not a valid date.");
    $players[] = [clean_text(post("ffa$i"), 30), $first, $last, null_if_empty($dob)];
}
if (count($players) < 5) fail('Please list at least 5 players.');

$staff = [];
foreach (['Coach' => 'coach', 'Manager' => 'manager', '3rd' => '3rd'] as $suffix => $type) {
    $name = clean_text(post("name$suffix"), 200);
    $email = post("email$suffix");
    $mobile = clean_text(post("mobile$suffix"), 20);
    if ($name === '' || ($email === '' && $mobile === '')) continue;
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) fail("Check the $type's email address.");
    $ex = post("wwvpEx$suffix");
    if ($ex !== '' && !valid_date($ex)) fail("Check the $type's WWVP expiry date.");
    $staff[] = [$name, $email, $mobile, clean_text(post("wwvpNo$suffix"), 50), null_if_empty($ex), $type];
}
if (!$staff || $staff[0][5] !== 'coach') fail('Please enter the coach and their email or mobile.');

$pdo = db();
$pdo->beginTransaction();
q('INSERT INTO teams (name, age, gender, division) VALUES (?, ?, ?, ?)', [$team, $age, $gender, $division]);
$teamId = (int) $pdo->lastInsertId();
foreach ($players as $p) q('INSERT INTO players (ffa, firstName, lastName, dob, team) VALUES (?, ?, ?, ?, ?)', [...$p, $teamId]);
foreach ($staff as $s) q('INSERT INTO staff (name, email, mobile, wwvpNo, wwvpEx, type, team) VALUES (?, ?, ?, ?, ?, ?, ?)', [...$s, $teamId]);
$pdo->commit();

// Confirmation to the coach (or manager) with what was submitted.
$to = [];
foreach ($staff as $s) if ($s[1] !== '' && !$to) $to[$s[1]] = $s[0];
$lines = ["Team: $team ($age $gender, division $division)", ''];
foreach ($staff as $s) $lines[] = ucfirst($s[5]) . ': ' . $s[0];
$lines[] = '';
foreach ($players as $n => $p) $lines[] = 'Player ' . ($n + 1) . ': ' . $p[1] . ' ' . $p[2];
$text = "Thank you for nominating your team. You will hear from us soon.\n\n" . implode("\n", $lines)
    . "\n\nReply to this email if anything needs changing.";
$mailed = $to ? send_mail('NCF team nomination received', nl2br(h($text)), $text, $to) : false;

json_out(['success' => true, 'message' => 'Team submitted.' . ($mailed ? ' A confirmation email is on its way.' : '')]);
