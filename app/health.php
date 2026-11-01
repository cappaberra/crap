<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain');
try { $row = db()->query('SELECT 1 FROM status WHERE ID=1')->fetch_row(); if (!$row) throw new RuntimeException('Missing status'); echo 'ok'; }
catch (Throwable $e) { http_response_code(503); echo 'unavailable'; }
