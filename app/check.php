<?php
// CLI only: the worker performs one bounded probe cycle per invocation.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/monitor.php';
$hosts = array_values(array_filter(array_map('trim', explode(',', env('MONITOR_HOSTS', '1.1.1.1,8.8.8.8,9.9.9.9')))));
if (!$hosts) throw new RuntimeException('MONITOR_HOSTS must contain at least one host');
$up = 0;
foreach ($hosts as $host) {
    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9.:-]*$/D', $host)) throw new RuntimeException('Invalid monitor host');
    exec('ping -n -c 1 -W 2 ' . escapeshellarg($host) . ' >/dev/null 2>&1', $output, $code);
    if ($code === 0) $up++;
}
$ratio = $up / count($hosts);
$threshold = (float) env('OUTAGE_THRESHOLD', '0.335');
if ($threshold < 0 || $threshold >= 1) throw new RuntimeException('OUTAGE_THRESHOLD must be in [0,1)');
record_probe(db(), $ratio, $threshold, time());
echo date(DATE_ATOM) . ' responding=' . $up . '/' . count($hosts) . ' status=' . ($ratio <= $threshold ? 'down' : 'up') . PHP_EOL;
