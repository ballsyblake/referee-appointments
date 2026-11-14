<?php
// Approve (1) or un-approve (0) a team. data = team id followed by 0/1.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$data = post('data');
if (!preg_match('/^(\d+)([01])$/', $data, $m)) fail('Bad request.');
q('UPDATE teams SET approved = ? WHERE id = ?', [(int) $m[2], (int) $m[1]]);
json_out(['success' => true]);
