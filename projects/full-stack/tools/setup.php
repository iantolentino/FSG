<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
echo "Choose a studio admin password (at least 12 characters): ";
$password = trim((string) fgets(STDIN));
if (strlen($password) < 12) { fwrite(STDERR, "Use at least 12 characters.\n"); exit(1); }
$target = __DIR__ . '/../server/config.local.php';
if (file_exists($target)) { fwrite(STDERR, "Configuration exists. Remove it explicitly to reset the password.\n"); exit(1); }
$config = ['admin_password_hash' => password_hash($password, PASSWORD_DEFAULT)];
file_put_contents($target, "<?php\nreturn " . var_export($config, true) . ";\n");
chmod($target, 0600);
echo "Configuration created outside public/. Run: php -S localhost:8000 -t public\n";
