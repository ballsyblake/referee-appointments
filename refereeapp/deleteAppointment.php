<?php
require __DIR__ . '/lib/bootstrap.php';
require_admin();
q('DELETE FROM appointments WHERE appointID = ?', [(int) post('id')]);
json_out(['success' => true]);
