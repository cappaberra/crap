<?php
function env(string $key, string $default = ''): string {
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}
date_default_timezone_set(env('TZ', 'America/Boise'));
function db(): mysqli {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli(env('DB_HOST', 'db'), env('DB_USER', 'crap2'), env('DB_PASSWORD'), env('DB_NAME', 'crap2'));
    $db->set_charset('utf8mb4');
    return $db;
}
