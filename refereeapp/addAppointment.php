<?php
// Admin adds one match, or imports many from a CSV file with the columns
// date,time,venue,court,agegroup (header row optional), e.g.
// 2026-11-20,17:00,lyn,1,U12 Open
require __DIR__ . '/lib/bootstrap.php';
require_admin();

function add_match(array $m, int $line = 0): ?string
{
    [$date, $time, $venue, $court, $age] = array_map(fn($v) => trim((string) $v), array_pad($m, 5, ''));
    $where = $line ? "Line $line: " : '';
    if (!valid_date($date)) return $where . 'date must look like 2026-11-20.';
    if (!valid_time($time)) return $where . 'time must look like 17:00.';
    $venue = strtolower($venue);
    if (!isset(VENUES[$venue])) return $where . 'venue must be lyn, gcr or rad.';
    if (!preg_match('/^U\d{1,2}\b/i', $age)) return $where . 'age group must start like U12.';
    q('INSERT INTO appointments (Date, MatchTime, Venue, Court, AgeGroup) VALUES (?, ?, ?, ?, ?)',
        [$date, $time, $venue, clean_text($court ?: '1', 20), strtoupper(substr($age, 0, 1)) . clean_text(substr($age, 1), 24)]);
    return null;
}

if (!empty($_FILES['csv']['tmp_name']) && is_uploaded_file($_FILES['csv']['tmp_name'])) {
    $fh = fopen($_FILES['csv']['tmp_name'], 'r');
    $pdo = db();
    $pdo->beginTransaction();
    $line = 0;
    $added = 0;
    while (($row = fgetcsv($fh)) !== false) {
        $line++;
        if ($line === 1 && strtolower(trim((string) $row[0])) === 'date') continue;
        if (count(array_filter($row, fn($c) => trim((string) $c) !== '')) === 0) continue;
        $err = add_match($row, $line);
        if ($err) {
            $pdo->rollBack();
            fail('Nothing imported. ' . $err);
        }
        $added++;
    }
    $pdo->commit();
    json_out(['success' => true, 'added' => $added]);
}

$err = add_match([post('date'), post('time'), post('venue'), post('court'), post('ageGroup')]);
if ($err) fail($err);
json_out(['success' => true, 'added' => 1]);
