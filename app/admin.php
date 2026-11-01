<?php
require_once __DIR__ . '/session.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf()) { http_response_code(403); $message = 'Invalid form token.'; }
    elseif (isset($_POST['logout'])) { unset($_SESSION['admin']); $message = 'Signed out.'; }
    elseif (env('ADMIN_PASSWORD') !== '' && is_string($_POST['password'] ?? null) && hash_equals(env('ADMIN_PASSWORD'), $_POST['password'])) {
        session_regenerate_id(true); $_SESSION['admin'] = true;
        header('Location: /'); exit;
    } else { $message = 'Invalid password, or administration is disabled.'; }
}
?>
<!doctype html><html lang="en"><meta charset="utf-8"><title>CRAP history administration</title>
<body><h1>History administration</h1><p><?= htmlspecialchars($message) ?></p>
<form method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
<?php if (!empty($_SESSION['admin'])): ?>
<button name="logout">Sign out</button>
<?php else: ?>
<label>Password <input type="password" name="password" required autocomplete="current-password"></label>
<button>Sign in</button>
<?php endif ?></form><p><a href="/">Return to dashboard</a></p></body></html>
