<?php
require __DIR__ . '/lib/bootstrap.php';
start_session();
$_SESSION = [];
session_destroy();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/']);
json_out(['success' => true]);
