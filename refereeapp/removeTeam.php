<?php
// Take a player off their team. data = player id.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
q('UPDATE players SET team = 0 WHERE id = ?', [(int) post('data')]);
json_out(['success' => true]);
