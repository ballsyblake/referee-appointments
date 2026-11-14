<?php
require __DIR__ . '/lib/bootstrap.php';
require_admin();
json_out(q('SELECT s.name, s.email, s.mobile, s.type, t.name AS team, s.registered
            FROM staff s LEFT JOIN teams t ON t.id = s.team ORDER BY t.name, s.type')->fetchAll(PDO::FETCH_ASSOC));
