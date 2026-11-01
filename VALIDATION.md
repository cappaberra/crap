# Conversion validation

Performed in the conversion workspace:

- All 15 recovered live trunk HEAD files matched GNU RCS 5.10.1 `co -p -ko` byte-for-byte, including image assets. Dead HEAD files stayed excluded from the runtime; all original archives remain in the separately supplied conversion ZIP, excluded from this repository because they contain historical credentials.
- All nine application PHP files passed PHP 8.3 syntax checks.
- Compose YAML parsed, including shared environment/service anchors; the worker passed shell syntax validation.
- Schema initialized successfully against MariaDB 10.11.14.
- The included smoke test passed all eight checks using PHP 8.3.6 with mysqli and MariaDB 10.11.14. PHP warnings were promoted to exceptions in this test.
- HTTP integration using PHP's development server passed: dashboard rendering, health endpoint, 14-field JSON response on an empty database, denial of unauthenticated deletion even with the legacy us=1 flag, session login, display of an active outage, refusal to delete that active outage, recovery display, authenticated deletion of a completed outage, and rendering after deletion.

Limitations: Docker is not installed in the conversion workspace. The exact PHP 8.4 Apache / MariaDB 11.4 images, Apache access rules, Compose service startup, container ICMP behavior, and persistence across Docker restarts were not executed here. No browser-based visual inspection was performed. Use the README startup and smoke-test commands on the target computer, and confirm that the monitor's logs show reachable hosts. No claim of full Docker end-to-end validation is made.
