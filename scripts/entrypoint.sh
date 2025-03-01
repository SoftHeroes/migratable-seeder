#!/usr/bin/env sh

set -e

composer install

exec "$@"
