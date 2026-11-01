<?php
require_once __DIR__ . '/config.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Strict', 'use_strict_mode' => true]);
}
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
function valid_csrf(): bool {
    return isset($_POST['csrf']) && is_string($_POST['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}
