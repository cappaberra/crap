<?php
require_once __DIR__ . '/session.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    if (($_POST['action'] ?? '') !== 'crap') { http_response_code(400); throw new RuntimeException('Invalid action'); }
    $id = filter_var($_POST['id'] ?? -1, FILTER_VALIDATE_INT);
    if ($id === false) { http_response_code(400); throw new RuntimeException('Invalid ID'); }
    if ($id > 0 && (empty($_SESSION['admin']) || !valid_csrf())) {
        http_response_code(403); throw new RuntimeException('Not authorized');
    }
    require_once __DIR__ . '/crap.php';
    $app = new crap(['id' => $id]);
    echo json_encode($app->return_response(), JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log((string)$e);
    if (http_response_code() < 400) http_response_code(500);
    echo json_encode(['error' => http_response_code() === 500 ? 'Unable to process request; check container logs.' : $e->getMessage()]);
}
