#!/usr/bin/env bash

set -e

# Note that .github/workflows/solid-tests-suites.yml does not use this, this function is just for manual runs of this script.
# You can pick different values for the NEXTCLOUD_VERSION build arg, as required:
function setup {
  docker build -t pubsub-server https://github.com/pdsinterop/php-solid-pubsub-server.git#main
  docker build -t solid-php -f docker/solid.Dockerfile .

  docker network create local

  docker pull solidtestsuite/solid-crud-tests:v7.0.5
  docker pull solidtestsuite/web-access-control-tests:v7.1.0
  docker pull solidtestsuite/webid-provider-tests:v2.1.1

  docker tag solidtestsuite/solid-crud-tests:v7.0.5 solid-crud-tests
  docker tag solidtestsuite/web-access-control-tests:v7.1.0 web-access-control-tests
  docker tag solidtestsuite/webid-provider-tests:v2.1.1 webid-provider-tests
}

function teardown {
  docker stop `docker ps --filter network=local -q`
  docker rm `docker ps --filter network=local -qa`
  docker network remove local
}

function startPubSub {
  docker run -d --name pubsub --network=local pubsub-server
}

function startSolidPhp {
  docker run -d --name "$1" --network-alias="id-alice.solid" --network-alias="storage-alice.solid" --network-alias="id-bob.solid" --network-alias="storage-bob.solid" --network=local "${2:-solid-php}"

  echo "Running init script for Solid PHP $1 ..."
  docker exec -w /opt/solid/ "$1" mkdir keys pods db
  docker exec -w /opt/solid/ "$1" chown -R www-data:www-data keys pods db
  docker exec -w /opt/solid/ "$1" cp tests/testsuite/config.php.testsuite config.php
  docker exec -u www-data -i -w /opt/solid/ "$1" php init.php
  docker exec -u www-data -i -w /opt/solid/ "$1" php tests/testsuite/init-testsuite.php

  export ALICE_COOKIE=`docker exec "$1" curl --output /dev/null --silent --insecure --dump-header - 'https://solid.local/login/password' -X POST -H 'Content-Type: application/x-www-form-urlencoded' --data-raw 'password=alice123&username=alice&response_type=&display=&scope=&client_id=&redirect_uri=&state=&nonce=&request=' |grep Set-Cookie |sort -u |sed -e 's/Set-Cookie: //'|sed -e 's/; expires=.*/; /' |tr -d '\n'`
  export BOB_COOKIE=`docker exec "$1" curl --output /dev/null --silent --insecure --dump-header - 'https://solid.local/login/password' -X POST -H 'Content-Type: application/x-www-form-urlencoded' --data-raw 'password=bob345&username=bob&response_type=&display=&scope=&client_id=&redirect_uri=&state=&nonce=&request=' |grep Set-Cookie |sort -u |sed -e 's/Set-Cookie: //'|sed -e 's/; expires=.*/; /' |tr -d '\n'`
}

function runTests {
  export RESULTS_PATH=testsuite-results.json
  export SKIP_CONC=1
  export SKIP_SECURE_WEBSOCKETS=1
  export SKIP_WEBHOOKS=1
  export NODE_TLS_REJECT_UNAUTHORIZED=0

  export PUBSUB=wss://pubsub:8080

  export ALICE_SERVER_ROOT=https://solid.local
  export ALICE_WEBID=https://id-alice.solid.local/#me
  export ALICE_OIDC_ISSUER=https://solid.local
  export ALICE_STORAGE_ROOT=https://storage-alice.solid.local
  export ALICE_SERVER_ROOT_ESCAPED=https:\/\/solid.local

  export BOB_SERVER_ROOT=https://solid.local
  export BOB_WEBID=https://id-bob.solid.local/#me
  export BOB_OIDC_ISSUER=https://solid.local
  export BOB_STORAGE_ROOT=https://storage-bob.solid.local

  echo "Alice session cookie: $ALICE_COOKIE"
  echo "Bob session cookie: $BOB_COOKIE"
  echo "Running $1 tests"
  docker run --rm --network=local \
    --name tester \
    --env RESULTS_PATH="$RESULTS_PATH" \
    --env SKIP_CONC="$SKIP_CONC" \
    --env SKIP_SECURE_WEBSOCKETS="$SKIP_SECURE_WEBSOCKETS" \
    --env SKIP_WEBHOOKS="$SKIP_WEBHOOKS" \
    --env NODE_TLS_REJECT_UNAUTHORIZED="$NODE_TLS_REJECT_UNAUTHORIZED" \
    --env PUBSUB="$PUBSUB" \
    --env ALICE_WEBID="$ALICE_WEBID" \
    --env WEBID_ALICE="$ALICE_WEBID" \
    --env COOKIE_ALICE="$ALICE_COOKIE" \
    --env OIDC_ISSUER_ALICE="$ALICE_OIDC_ISSUER" \
    --env STORAGE_ROOT_ALICE="$ALICE_STORAGE_ROOT" \
    --env BOB_WEBID="$BOB_WEBID" \
    --env WEBID_BOB="$BOB_WEBID" \
    --env COOKIE_BOB="$BOB_COOKIE" \
    --env OIDC_ISSUER_BOB="$BOB_OIDC_ISSUER" \
    --env STORAGE_ROOT_BOB="$BOB_STORAGE_ROOT" \
    --env COOKIE="$ALICE_COOKIE" \
    --env STORAGE_ROOT="$ALICE_STORAGE_ROOT" \
    --env SERVER_ROOT="$ALICE_SERVER_ROOT" \
    --env SERVER_ROOT_ECSAPED="$ALICE_SERVER_ROOT_ESCAPED" \
    --env SECOND_SERVER_ROOT="$BOB_SERVER_ROOT" \
    --env OIDC_ISSUER="$ALICE_OIDC_ISSUER" \
    "$1-tests"
}

run_solid_test_suite() {
    # ...
    teardown || true
    setup
    startPubSub
    startSolidPhp solid
    runTests webid-provider
    runTests web-access-control
    runTests solid-crud
    teardown
}

if [ "${BASH_SOURCE[0]}" == "${0}" ]; then
  run_solid_test_suite "${@}"
else
    export -f run_solid_test_suite
    export -f runTests
    export -f setup
    export -f startPubSub
    export -f startSolidPhp
    export -f teardown
fi
