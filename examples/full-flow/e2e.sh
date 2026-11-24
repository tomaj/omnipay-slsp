#!/usr/bin/env bash
# The whole redirect flow on localhost: a shop and a fake SporoPay endpoint, each on `php -S`.
# browser.php plays the customer. No real bank and no money involved.
set -euo pipefail
cd "$(dirname "$0")"
[[ -f vendor/autoload.php ]] || { echo "fail: vendor/ is missing, run composer install first" >&2; exit 1; }

free_port() { php -r '$s = stream_socket_server("tcp://127.0.0.1:0"); echo explode(":", stream_socket_get_name($s, false))[1];'; }
SHOP_DATA=$(mktemp -d)
SHOP_PORT=$(free_port)
BANK_PORT=$(free_port)
export SHOP_DATA BANK_URL="http://127.0.0.1:$BANK_PORT"
# the servers are disowned, so bash prints no "Terminated" job message when they are stopped
stop() {
    kill "$SHOP" "$BANK" 2>/dev/null || true
    for _ in $(seq 1 50); do kill -0 "$SHOP" 2>/dev/null || kill -0 "$BANK" 2>/dev/null || break; sleep 0.1; done
    rm -rf "$SHOP_DATA"
}
trap stop EXIT

php -S "127.0.0.1:$SHOP_PORT" shop/index.php > "$SHOP_DATA/shop.log" 2>&1 & SHOP=$!; disown
php -S "127.0.0.1:$BANK_PORT" bank/index.php > "$SHOP_DATA/bank.log" 2>&1 & BANK=$!; disown
for port in "$SHOP_PORT" "$BANK_PORT"; do
    for _ in $(seq 1 50); do php -r 'exit(@fsockopen("127.0.0.1", (int) $argv[1]) ? 0 : 1);' "$port" && break; sleep 0.1; done
done

shop="http://127.0.0.1:$SHOP_PORT"
echo "== the customer pays order 2024000123"
php browser.php "$shop" 2024000123 OK
echo "== the customer cancels order 2024000124 in the bank"
php browser.php "$shop" 2024000124 NOK
echo "== the customer changes the amount of order 2024000124"
php browser.php "$shop" 2024000124 OK tamper
echo "== somebody opens a forged success URL for order 2024000124"
php browser.php "$shop" "/return?vs=2024000124&suma=24.90&mena=EUR&result=OK&real=OK&SIGN2=AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
echo "== the orders"
php browser.php "$shop" /orders
