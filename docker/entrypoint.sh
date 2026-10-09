#!/bin/sh
set -e

php artisan stockflow:boot

exec "$@"
