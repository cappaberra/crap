#!/bin/sh
set -eu
case "${CHECK_INTERVAL:-10}" in ''|*[!0-9]*|0) echo 'CHECK_INTERVAL must be a positive integer' >&2; exit 1;; esac
trap 'exit 0' TERM INT
while :; do
    php /var/www/html/check.php
    sleep "${CHECK_INTERVAL:-10}" &
    wait $!
done
