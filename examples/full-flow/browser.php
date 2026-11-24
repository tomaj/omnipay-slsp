<?php

declare(strict_types=1);

// Plays the customer's browser: php browser.php <shop url> <order> <choice> [tamper]
// Opens the checkout, follows the redirect to the bank, "clicks" the choice there, follows the bank's
// redirect back to the shop and prints what each side answered (without host names and ports).
// With a path instead of an order (php browser.php <shop url> /orders) it only opens that page.

$shop = $argv[1] ?? 'http://127.0.0.1:8000';
$order = $argv[2] ?? '2024000123';
$choice = $argv[3] ?? 'OK';
$tamper = ($argv[4] ?? '') === 'tamper';

/**
 * @return array{int, string, ?string} status, body, Location header
 */
function get(string $url): array
{
    $context = stream_context_create(['http' => ['follow_location' => 0, 'ignore_errors' => true]]);
    $body = (string) file_get_contents($url, false, $context);
    $headers = http_get_last_response_headers() ?? [];
    $status = (int) explode(' ', $headers[0] ?? 'HTTP/1.1 0')[1];
    $location = null;
    foreach ($headers as $header) {
        if (stripos($header, 'Location:') === 0) {
            $location = trim(substr($header, 9));
        }
    }

    return [$status, $body, $location];
}

/**
 * A query string parameter of $url, '' when it is missing.
 */
function query(string $url, string $name): string
{
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    $value = $query[$name] ?? '';

    return is_string($value) ? $value : '';
}

if (str_starts_with($order, '/')) {
    [$status, $body] = get($shop . $order);
    echo "shop  {$status} " . $body;
    return;
}

[$status, , $bank] = get("{$shop}/checkout?order={$order}");
$bank = (string) $bank;
echo "shop  {$status} redirect to " . parse_url($bank, PHP_URL_PATH) . ', vs=' . query($bank, 'vs') . ', suma=' . query($bank, 'suma') . "\n";

if ($tamper) {
    $bank = str_replace('suma=' . query($bank, 'suma'), 'suma=0.01', $bank);
    echo "      the customer edits suma to 0.01 in the address bar\n";
}

[$status, $body, $location] = get($bank . '&choice=' . $choice);
if ($location === null) {
    echo "bank  {$status} " . $body;
    return;
}
parse_str((string) parse_url($location, PHP_URL_QUERY), $return);
// SIGN2 covers url, whose port changes from run to run, so only its presence is printed
echo "bank  {$status} redirect to " . parse_url($location, PHP_URL_PATH) . ' with ' . count($return) . ' fields, result=' . query($location, 'result') . ', SIGN2 ' . (query($location, 'SIGN2') !== '' ? 'set' : 'missing') . "\n";

[$status, $body] = get($location);
echo "shop  {$status} " . $body;
