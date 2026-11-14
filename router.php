<?php
// Router for PHP's built-in server (local development only):
//   php -S localhost:8000 router.php
// It blocks the same paths .htaccess blocks on Apache.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('#^/(db|storage|uploads|phpMailer|refereeapp/lib)(/|$)#', $path)
    || preg_match('#(^|/)(config(\.example)?\.php|router\.php|\.[^/]*)$#', $path)
    || preg_match('#\.(sql|md|log)$#', $path)) {
    http_response_code(404);
    echo 'Not found';
    return true;
}
if ($path === '/') {
    $_SERVER['SCRIPT_NAME'] = '/index.html';
    readfile(__DIR__ . '/index.html');
    return true;
}
return false; // let the built-in server serve the file as normal
