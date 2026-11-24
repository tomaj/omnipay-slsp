# FAQ

## Why does `completePurchase()` throw "The vs parameter is required" or "The amount parameter is required"?

Since 4.0 you pass the variable symbol and the amount of the order you are completing: `completePurchase(['vs' => $order->vs, 'amount' => $order->amount])`. It stops a valid return of one order, or of a smaller amount, from completing another. See [Upgrade from 3.x](./upgrade).

## "Amount mismatch" although the customer paid the right amount

The amount is compared as formatted text. Omnipay formats your `amount` with two decimals (`10` becomes `'10.00'`), and the bank returns `suma` as `purchase()` sent it. Pass the same amount to both, and make sure the order's amount did not change after the redirect. See the [Amount formats example](./examples/amount-formats).

## "incorect signature" on every return

- the `sharedSecret` differs from the one the return was signed with (test mode uses the simulator's key, production the key from the bank);
- your router changes or decodes the query string twice (`SIGN2` is base64 and may contain `+` and `/`);
- in a framework or a long-running server the gateway reads another request than the current one: pass it, `new Gateway(null, $request)`.

The message is spelled `incorect` in the code; match on the exception class, not the text.

## "Shared secret must be base64 encoded"

Since 4.0 the secret is decoded strictly. Remove quotes or other characters around the value in your configuration, and use the standard base64 alphabet (`+` and `/`, not `-` and `_`).

## The customer comes back without the return parameters

Then there is no `SIGN2` and `completePurchase()` throws `InvalidRequestException('incorect signature')`. Show the customer the order with a "pay again" option.

## What is `real`?

Another status field of the bank's return, `OK` or `NOK`, covered by `SIGN2`. `getReal()` returns it; `isSuccessful()` looks only at `result`.

## Is there a server-to-server notification?

No. SporoPay only redirects the customer back. If the customer closes the browser after paying, your shop learns about the payment from the bank statement.

## Can I refund, capture or ask for the payment status?

No. SporoPay is a payment button: `purchase()` and `completePurchase()`. Refunds are bank transfers.

## Which currencies are supported?

Euro only: `mena` is always `EUR`, and a return with another currency is rejected.

## Can I use it without Omnipay's HTTP client?

The gateway makes no HTTP requests, but Omnipay's constructor discovers a PSR-18 client. Install one (`php-http/guzzle7-adapter`) or pass your own `Omnipay\Common\Http\ClientInterface` to the constructor.

## How do I use it in Laravel, Symfony or Nette?

Create the gateway per request and pass the current HTTP request (a Symfony `Request`; Laravel's request extends it). Call `redirect()` or return `getRedirectResponse()` in the checkout action, `completePurchase()` in the return action. See [Configuration](./guide/configuration#the-http-request-in-frameworks).

## Why are the classes `final`?

So that the signature and verification code cannot be changed by accident in a subclass. Wrap the gateway in your own service instead of extending it; see [Upgrade from 3.x](./upgrade#final-classes).
