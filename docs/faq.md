# FAQ

## Why does `completePurchase()` throw "The vs parameter is required" or "The amount parameter is required"?

Since 4.0 you have to pass the variable symbol and amount of the order you are completing: `completePurchase(['vs' => $order->vs, 'amount' => $order->amount])`. It stops a valid return of one order, or of a smaller amount, from completing another. See [UPGRADE-4.0.md](https://github.com/tomaj/omnipay-slsp/blob/master/UPGRADE-4.0.md).

## "Amount mismatch" although the customer paid the right amount

The amount is compared as formatted text. Omnipay formats your `amount` with two decimals (`10` becomes `'10.00'`); the bank returns `suma` as you sent it in `purchase()`. Pass the same amount to both.

## "incorect signature" on every return

- the `sharedSecret` differs from the one used to sign (test mode uses the simulator's key, production the key from the bank);
- the return parameters were changed or URL-decoded twice by your router;
- your framework does not populate `$_GET`: pass the current request to the gateway (`new Gateway(null, $request)`).

## "Shared secret must be base64 encoded"

Since 4.0 the secret is decoded strictly. Remove quotes or other characters around the value in your configuration.

## A cancelled payment comes back without parameters

Then there is no `SIGN2` and `completePurchase()` throws `InvalidRequestException('incorect signature')`. Show the customer the order with a "pay again" option.

## Can I use it without Omnipay's HTTP client?

The gateway makes no HTTP requests, but Omnipay's constructor discovers a PSR-18 client. Install one (`php-http/guzzle7-adapter`) or pass your own `Omnipay\Common\Http\ClientInterface` to the constructor.

## Which currencies are supported?

Euro only: `mena` is always `EUR`.
