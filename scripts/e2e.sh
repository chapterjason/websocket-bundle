#!/bin/sh
# Runs the e2e suite against a cluster of FrankenPHP built with the frankenphp-websocket extension.
set -eu

cd "$(dirname "$0")/.."

trap 'docker compose down --volumes >/dev/null 2>&1' EXIT

docker compose up --detach --wait a b
docker compose run --rm --no-deps phpunit "$@"
