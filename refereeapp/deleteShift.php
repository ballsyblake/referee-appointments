<?php
require __DIR__ . '/lib/bootstrap.php';
require_admin();
q('DELETE FROM shifts WHERE shiftID = ?', [(int) post('data')]);
json_out(['success' => true]);
