<?php
// Who is logged in. Pages call this on load and send the user to the login
// page on a 401.
require __DIR__ . '/lib/bootstrap.php';
$user = require_login();
json_out($user);
