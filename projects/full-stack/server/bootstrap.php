<?php
declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);
session_set_cookie_params(['httponly'=>true, 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite'=>'Lax', 'path'=>'/']);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
function db(): PDO {
    static $db;
    if ($db instanceof PDO) return $db;
    $path = getenv('STUDIO_DB_PATH') ?: __DIR__ . '/../data/studio.sqlite';
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0750, true);
    $db = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $db->exec('PRAGMA busy_timeout = 5000');
    $db->exec("CREATE TABLE IF NOT EXISTS requests (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL, message TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'new', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    return $db;
}
function send(array $body, int $status=200): never {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
    echo json_encode($body, JSON_THROW_ON_ERROR); exit;
}
function session_info(): array { return ['csrf'=>$_SESSION['csrf'], 'authenticated'=>!empty($_SESSION['admin'])]; }
function require_admin(): void { if (empty($_SESSION['admin'])) send(['error'=>'Sign in to manage project requests.'],401); }
function check_csrf(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) send(['error'=>'Refresh the page and try again.'],403);
}
function input(): array {
    try { $body = json_decode(file_get_contents('php://input'), false, 512, JSON_THROW_ON_ERROR); }
    catch (JsonException $e) { send(['error'=>'Send valid JSON.'],400); }
    if (!$body instanceof stdClass) send(['error'=>'Send a JSON object.'],400);
    return get_object_vars($body);
}
function clean(array $body): array {
    $values=[];
    foreach (['name'=>80,'email'=>254,'message'=>1000] as $field=>$limit) {
        $value=$body[$field] ?? '';
        if (!is_string($value)) send(['error'=>'Fields must contain text.'],422);
        $value=trim($value);
        if ($value==='' || strlen($value)>$limit) send(['error'=>'Check the ' . $field . ' field and its length.'],422);
        $values[$field]=$value;
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) send(['error'=>'Enter a valid email address.'],422);
    return $values;
}
function request_id(array $body): int {
    $id=filter_var($body['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if ($id===false || $id===null) send(['error'=>'A valid request id is required.'],422);
    $stmt=db()->prepare('SELECT id FROM requests WHERE id=?');$stmt->execute([$id]);
    if (!$stmt->fetch()) send(['error'=>'Project request not found.'],404);
    return $id;
}
