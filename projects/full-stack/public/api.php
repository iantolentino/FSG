<?php
declare(strict_types=1);
require __DIR__ . '/../server/bootstrap.php';
try {
    $action=$_GET['action'] ?? '';
    $method=$_SERVER['REQUEST_METHOD'];
    $methods=['session'=>'GET','create'=>'POST','list'=>'GET','update'=>'PATCH','delete'=>'DELETE','login'=>'POST','logout'=>'POST'];
    if (!is_string($action) || !isset($methods[$action])) send(['error'=>'Unknown action.'],404);
    if ($method!==$methods[$action]) { header('Allow: '.$methods[$action]); send(['error'=>'Method not allowed.'],405); }
    if ($method!=='GET') check_csrf();
    if ($action==='session') send(session_info());
    if ($action==='login') {
        $body=input();
        $configFile=getenv('STUDIO_CONFIG_PATH') ?: __DIR__.'/../server/config.local.php';
        if (!file_exists($configFile)) send(['error'=>'Run php tools/setup.php to configure the admin password.'],503);
        $config=require $configFile;
        $password=$body['password'] ?? '';
        if (time()-($_SESSION['login_attempt_at'] ?? 0)<2) send(['error'=>'Wait a moment before trying again.'],429);
        $_SESSION['login_attempt_at']=time();
        if (!is_string($password) || !password_verify($password,$config['admin_password_hash'])) send(['error'=>'Incorrect admin password.'],401);
        session_regenerate_id(true);$_SESSION['admin']=true;$_SESSION['csrf']=bin2hex(random_bytes(32));send(session_info());
    }
    if ($action==='logout') { $_SESSION=[];session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));send(session_info()); }
    if ($action==='create') {
        $values=clean(input());
        if (time()-($_SESSION['last_submission'] ?? 0)<3) send(['error'=>'Wait a moment before submitting again.'],429);
        $stmt=db()->prepare('INSERT INTO requests (name,email,message) VALUES (:name,:email,:message)');
        $stmt->execute($values);$_SESSION['last_submission']=time();send(['id'=>(int)db()->lastInsertId()],201);
    }
    require_admin();
    if ($action==='list') send(['requests'=>db()->query('SELECT * FROM requests ORDER BY id DESC')->fetchAll()]);
    $body=input();$id=request_id($body);
    if ($action==='update') {
        $values=clean($body);$status=$body['status'] ?? 'new';
        if (!is_string($status) || !in_array($status,['new','in-progress','done'],true)) send(['error'=>'Choose a valid status.'],422);
        $stmt=db()->prepare('UPDATE requests SET name=:name,email=:email,message=:message,status=:status WHERE id=:id');
        $stmt->execute($values+['status'=>$status,'id'=>$id]);send(['updated'=>true]);
    }
    if ($action==='delete') { $stmt=db()->prepare('DELETE FROM requests WHERE id=?');$stmt->execute([$id]);send(['deleted'=>true]); }
} catch (Throwable $error) {
    error_log($error->getMessage());send(['error'=>'The server could not complete your request. Check the private server log.'],500);
}
