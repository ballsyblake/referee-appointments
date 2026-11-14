<?php
// Save referee appointments from the admin schedule page.
// temp[<slot><appointID>] = "First Last", slot 1-4 = referee1-4, 5 = coach.
// An empty value clears the slot.
require __DIR__ . '/lib/bootstrap.php';
require_admin();

$temp = $_POST['temp'] ?? [];
if (!is_array($temp)) fail('Bad request.');
$columns = ['1' => 'referee1', '2' => 'referee2', '3' => 'referee3', '4' => 'referee4', '5' => 'coach'];

// Resolve names to usernames up front so a typo saves nothing.
$updates = [];
$errors = [];
foreach ($temp as $key => $name) {
    $key = (string) $key;
    $slot = substr($key, 0, 1);
    $id = substr($key, 1);
    if (!isset($columns[$slot]) || !ctype_digit($id) || !is_string($name)) continue;
    $name = trim($name);
    if ($name === '') {
        $updates[] = [$columns[$slot], null, (int) $id];
        continue;
    }
    $matches = q("SELECT username FROM referees WHERE CONCAT(FirstName, ' ', LastName) = ?", [$name])->fetchAll(PDO::FETCH_COLUMN);
    if (count($matches) !== 1) {
        $matches = q("SELECT username FROM referees WHERE username = ?", [$name])->fetchAll(PDO::FETCH_COLUMN);
    }
    if (count($matches) === 1) {
        $updates[] = [$columns[$slot], $matches[0], (int) $id];
    } else {
        $errors[] = $name;
    }
}
if ($errors) {
    text_out('Nothing saved. Could not find exactly one referee called: ' . h(implode(', ', array_unique($errors))));
}
$pdo = db();
$pdo->beginTransaction();
foreach ($updates as [$col, $username, $id]) {
    q("UPDATE appointments SET $col = ? WHERE appointID = ?", [$username, $id]);
}
$pdo->commit();
text_out('Schedule saved.');
