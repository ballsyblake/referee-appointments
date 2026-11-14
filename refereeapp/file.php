<?php
// Serves uploaded files to logged-in users. Uploads are not reachable
// directly (uploads/.htaccess denies them), so this is the only way in.
// Profile photos: any logged-in user. Other files (first aid
// certificates): admins only.
require __DIR__ . '/lib/bootstrap.php';
$user = current_user();
if ($user === null) {
    http_response_code(401);
    exit;
}

$name = basename((string) ($_GET['f'] ?? ''));
$path = cfg('UPLOAD_DIR') . '/' . $name;
if ($name === '' || !preg_match('/^[a-f0-9]{32}\.(jpg|png|gif|pdf|doc|docx)$/', $name) || !is_file($path)) {
    $path = NCF_ROOT . '/uploads/avatar.png';
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!str_starts_with($mime, 'image/') && $user['type'] !== 'admin') {
    http_response_code(403);
    exit;
}
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
if (!str_starts_with($mime, 'image/')) {
    header('Content-Disposition: attachment; filename="' . $name . '"');
}
readfile($path);
