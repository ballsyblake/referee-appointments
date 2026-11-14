<?php
// Players. username = 'all' or a team id.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$sql = 'SELECT p.id, p.ffa, p.firstName, p.lastName, p.dob, p.team, p.registered, t.name AS teamName
        FROM players p LEFT JOIN teams t ON t.id = p.team';
$team = post('username');
if (ctype_digit($team)) {
    json_out(q("$sql WHERE p.team = ? ORDER BY p.lastName, p.firstName", [(int) $team])->fetchAll(PDO::FETCH_ASSOC));
}
json_out(q("$sql ORDER BY t.name, p.lastName, p.firstName")->fetchAll(PDO::FETCH_ASSOC));
