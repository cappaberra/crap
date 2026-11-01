<?php
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
// Temporary tables shadow application tables on this connection; history is untouched.
require_once (getenv('APP_DIR') ?: '/var/www/html') . '/crap.php';
require_once (getenv('APP_DIR') ?: '/var/www/html') . '/monitor.php';
function check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
$db = db();
foreach (['status', 'downtime'] as $table) {
    $ddl = $db->query('SHOW CREATE TABLE ' . $table)->fetch_row()[1];
    $db->query(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $ddl));
}
$db->query('INSERT INTO status VALUES(1,1,0,1000)');
record_probe($db, 1.0, .335, 1010);
check((int)$db->query('SELECT COUNT(*) FROM downtime')->fetch_row()[0] === 0, 'healthy probe does not create outage');
record_probe($db, 1/3, .335, 1020);
record_probe($db, 0, .335, 1030);
check((int)$db->query('SELECT COUNT(*) FROM downtime')->fetch_row()[0] === 1, 'repeated failed probes create one outage');
$row = $db->query('SELECT * FROM downtime')->fetch_assoc();
check(abs((float)$row['hostsup'] - 1/3) < .00001, 'fractional host response retained');
record_probe($db, 2/3, .335, 1050);
check((int)$db->query('SELECT total FROM downtime')->fetch_row()[0] === 30, 'recovery records elapsed outage duration');
check((int)$db->query('SELECT status FROM status')->fetch_row()[0] === 1, 'recovery restores up state');
$db->query('ALTER TABLE downtime AUTO_INCREMENT=10');
record_probe($db, 0, .335, 1060);
check((int)$db->query('SELECT lastdown FROM status')->fetch_row()[0] === 10, 'actual inserted ID used after gaps');
record_probe($db, 1, .335, 1070);
$_SESSION = [];
$app = new crap(['id' => -1]);
$payload = $app->return_response();
check(count($payload) === 14 && isset($payload['id_hist']), 'dashboard response has all expected fields');
check(json_encode($payload, JSON_THROW_ON_ERROR) !== '', 'dashboard JSON serializes');
