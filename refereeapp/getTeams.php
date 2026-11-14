<?php
// Team nominations. username = 'pending', 'approved' or anything else for all.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$filter = ['pending' => ' WHERE approved = 0', 'approved' => ' WHERE approved = 1'][post('username')] ?? '';
json_out(q("SELECT t.id, t.name, t.age, t.gender, t.division, t.approved,
                   (SELECT COUNT(*) FROM players p WHERE p.team = t.id) AS players
            FROM teams t$filter ORDER BY t.age, t.gender, t.division, t.name")->fetchAll(PDO::FETCH_ASSOC));
