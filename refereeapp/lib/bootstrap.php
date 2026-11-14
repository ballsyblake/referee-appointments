<?php
// Shared setup for every endpoint in refereeapp/.
//
// Each endpoint starts with:  require __DIR__ . '/lib/bootstrap.php';
// and then calls one of require_admin(), require_login() or public_endpoint()
// before touching the database.
//
// Configuration comes from environment variables first, then from
// refereeapp/config.php (git-ignored). See config.example.php.

declare(strict_types=1);

const NCF_ROOT = __DIR__ . '/../..';

// ---------------------------------------------------------------- config

function ncf_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $file = [];
    $path = __DIR__ . '/../config.php';
    if (is_file($path)) {
        $loaded = include $path;
        if (is_array($loaded)) {
            $file = $loaded;
        }
        // Old-style config.php that only define()s constants.
        foreach (['DBHOST' => 'DB_HOST', 'DBUSER' => 'DB_USER', 'DBPWD' => 'DB_PASS',
                  'DBNAME' => 'DB_NAME', 'SMTP_USER' => 'SMTP_USER', 'SMTP_PASS' => 'SMTP_PASS'] as $const => $key) {
            if (defined($const) && !isset($file[$key])) {
                $file[$key] = constant($const);
            }
        }
    }
    $defaults = [
        'DB_HOST' => '127.0.0.1',
        'DB_PORT' => '3306',
        'DB_NAME' => 'ncf',
        'DB_USER' => 'root',
        'DB_PASS' => '',
        // smtp = send for real, log = write to storage/mail.log, off = drop
        'MAIL_MODE' => 'log',
        'SMTP_HOST' => 'smtp.gmail.com',
        'SMTP_PORT' => '587',
        'SMTP_USER' => '',
        'SMTP_PASS' => '',
        'MAIL_FROM' => '',
        'MAIL_FROM_NAME' => 'North Canberra Futsal',
        'ADMIN_EMAIL' => '',          // gets venue manager applications
        'APP_URL' => '',              // used in email links, e.g. https://refs.example.com
        'UPLOAD_DIR' => NCF_ROOT . '/uploads',
        'TIMEZONE' => 'Australia/Sydney',
        'DEBUG' => '0',
        // '1' on a public demo: shows demo logins on the login page and
        // blocks password changes so visitors can't lock each other out.
        'DEMO' => '0',
    ];
    $config = [];
    foreach ($defaults as $key => $default) {
        $env = getenv('NCF_' . $key);
        if ($env !== false && $env !== '') {
            $config[$key] = $env;
        } elseif (isset($file[$key]) && $file[$key] !== '') {
            $config[$key] = (string) $file[$key];
        } else {
            $config[$key] = $default;
        }
    }
    return $config;
}

function cfg(string $key): string
{
    return ncf_config()[$key] ?? '';
}

date_default_timezone_set(cfg('TIMEZONE'));

// ---------------------------------------------------------------- errors

// Never show database errors to the browser: they leak table names and
// sometimes data. Log them and send a generic message instead.
set_exception_handler(function (Throwable $e) {
    error_log('[ncf] ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    $body = ['success' => false, 'error' => 'Server error, please try again.'];
    if (cfg('DEBUG') === '1') {
        $body['detail'] = $e->getMessage();
    }
    echo json_encode($body);
});
ini_set('display_errors', cfg('DEBUG') === '1' ? '1' : '0');

// ---------------------------------------------------------------- database

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            cfg('DB_HOST'), cfg('DB_PORT'), cfg('DB_NAME'));
        $pdo = new PDO($dsn, cfg('DB_USER'), cfg('DB_PASS'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_BOTH,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        if (cfg('DEMO') === '1' && PHP_SAPI !== 'cli') {
            require_once __DIR__ . '/seed.php';
            ncf_demo_autoreset($pdo);
        }
    }
    return $pdo;
}

/** Run a prepared statement and return it. */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// ---------------------------------------------------------------- output

function json_out($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function text_out(string $text, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $text;
    exit;
}

function fail(string $message, int $status = 400): void
{
    json_out(['success' => false, 'error' => $message], $status);
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------- sessions

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('ncf_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,      // JavaScript cannot read the session cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** @return array{username:string,type:string}|null */
function current_user(): ?array
{
    start_session();
    if (empty($_SESSION['username']) || empty($_SESSION['type'])) {
        return null;
    }
    return ['username' => $_SESSION['username'], 'type' => $_SESSION['type']];
}

/**
 * Stop cross-site request forgery: a POST must come from our own pages.
 * jQuery adds X-Requested-With to same-origin AJAX calls; another site
 * cannot add that header without a CORS preflight, which we never allow.
 */
function csrf_check(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
        fail('Bad request.', 403);
    }
}

function public_endpoint(): void
{
    csrf_check();
}

/** Any logged-in user, optionally limited to some types. */
function require_login(array $types = ['admin', 'referee', 'venuemanager']): array
{
    $user = current_user();
    if ($user === null) {
        fail('Please log in.', 401);
    }
    if (!in_array($user['type'], $types, true)) {
        fail('Not allowed.', 403);
    }
    csrf_check();
    return $user;
}

function require_admin(): array
{
    return require_login(['admin']);
}

// ---------------------------------------------------------------- input

function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** Free text from a user: no HTML, limited length. */
function clean_text(string $s, int $max = 255): string
{
    $s = trim(strip_tags($s));
    return mb_substr($s, 0, $max);
}

function valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d !== false && $d->format('Y-m-d') === $s;
}

function valid_time(string $s): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $s);
}

function null_if_empty(string $s): ?string
{
    return $s === '' ? null : $s;
}

/** Stop an action on the public demo site. */
function forbid_in_demo(string $what): void
{
    if (cfg('DEMO') === '1') {
        fail("$what is turned off on the demo site.", 403);
    }
}

const VENUES = ['lyn' => 'Lyneham', 'gcr' => 'Gold Creek', 'rad' => 'Radford'];

// ---------------------------------------------------------------- uploads

/**
 * Save an uploaded file under a random name and return that name.
 * Returns null when no file was sent. Calls fail() on a bad file.
 */
function save_upload(string $field, array $allowed): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        fail('Upload failed, please try again.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        fail('File is too large (5 MB max).');
    }
    // Trust the file's content, not its name: check the real MIME type.
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        fail('That file type is not allowed.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $dir = cfg('UPLOAD_DIR');
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Upload folder missing: ' . $dir);
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Could not move upload to ' . $dir);
    }
    return $name;
}

const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
const DOCUMENT_TYPES = IMAGE_TYPES + [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
];

// ---------------------------------------------------------------- dates

/**
 * Fixture weekend shown to referees and venue managers: the Friday and
 * Sunday of the current Monday-to-Sunday week.
 */
function this_friday(): string
{
    return date('Y-m-d', strtotime('monday this week +4 days'));
}

function this_sunday(): string
{
    return date('Y-m-d', strtotime('monday this week +6 days'));
}

/**
 * Weekend that availability is being submitted for: the next Friday after
 * today and the Sunday after it.
 */
function availability_friday(): string
{
    return date('Y-m-d', strtotime('next friday'));
}

function availability_sunday(): string
{
    return date('Y-m-d', strtotime(availability_friday() . ' +2 days'));
}
