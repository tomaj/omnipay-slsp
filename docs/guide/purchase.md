# Starting a payment

`purchase()` builds the signed payment request. Nothing is sent from your server: SporoPay is a GET redirect, so you send the customer's browser to a URL that carries every field and the signature `sign1`.

<<< @/../examples/purchase-redirect/run.php{php}

Output (the [Payment redirect example](../examples/purchase-redirect)):

<<< @/../examples/purchase-redirect/expected-output.txt{text}

## The request

`$gateway->purchase(array $parameters)` returns a `PurchaseRequest` with the gateway's parameters plus yours ([all parameters](./configuration#purchase-parameters)). `send()` validates the parameters, adds `sign1` and returns a `PurchaseResponse`. It throws:

| Exception | When |
|---|---|
| `InvalidRequestException('The pu_predcislo parameter is required')`, `... pu_cislo ...`, `... vs ...`, `... ss ...`, `... rurl ...`, `... param ...`, `... amount ...` | the parameter is `null` |
| `InvalidRequestException('The pu_predcislo parameter must be up to 6 digits')`, `('The pu_cislo parameter must be up to 10 digits')` | the account number prefix is not 1 to 6 digits, or the account number not 1 to 10 digits (no dashes, spaces, bank code or IBAN) |
| `InvalidRequestException('The vs parameter must be up to 10 digits')`, `... ss ...` | `vs` or `ss` is not 1 to 10 digits (`0`-`9` only, no spaces or signs) |
| `InvalidRequestException('The rurl parameter must be an absolute https:// URL')` | `rurl` is not an absolute `https://` URL with a host (relative, another scheme, or containing whitespace, control characters or `;`); in test mode `http://` is accepted too and the message is `... must be an absolute https:// or http:// URL` |
| `InvalidRequestException('The param parameter must not contain control characters or ";"')` | `param` contains a line break, tab, NUL or another control character, or `;` (the separator of the signed fields) |
| `InvalidRequestException('Amount precision is too high for currency.')` | `amount` has more than two decimals |
| `InvalidRequestException('A negative amount is not allowed.')` | `amount` is negative |
| `Exception\InvalidArgumentException('Shared secret must not be empty')` | the shared secret is empty or only whitespace (the gateway's default is `''`) |
| `Exception\InvalidArgumentException('Shared secret must be base64 encoded')` | the shared secret is not strict base64 |
| `Exception\InvalidArgumentException('Shared secret must be a 16- or 24-byte 3DES key')` | the decoded shared secret has another length |
| `Exception\RuntimeException('Unable to compute the DES-EDE3-CBC signature: ...')` | OpenSSL failed to encrypt |

The `InvalidRequestException`s are thrown by `getData()`, before anything is signed, so a malformed payment never reaches SporoPay's error page. Accepted formats:

| Parameter | Format |
|---|---|
| `accountNumberPrefix` (`pu_predcislo`) | 1 to 6 digits (`000000` without a prefix) |
| `accountNumber` (`pu_cislo`) | 1 to 10 digits |
| `vs`, `ss` | 1 to 10 digits |
| `rurl` | absolute `https://` URL with a host, no whitespace, control characters or `;`; `http://` only in test mode |
| `param` | any text without control characters and `;`, `''` included |

`getData()` returns the fields without the signature, in this order: `pu_predcislo`, `pu_cislo`, `pu_kbanky`, `suma`, `mena`, `vs`, `ss`, `url`, `param`. `sendData($data)` adds `sign1`.

## The response

| Method | Returns |
|---|---|
| `isSuccessful()` | `false`: the payment has not happened yet |
| `isRedirect()` | `true` |
| `getRedirectMethod()` | `'GET'` |
| `getRedirectUrl()` | the endpoint with the fields and `sign1` in the query string (`http_build_query()`, so a `null` field is left out) |
| `getRedirectData()` | the fields of `getData()` plus `sign1` |
| `redirect()` | sends a 302 redirect to `getRedirectUrl()` (Omnipay) |
| `getRedirectResponse()` | the same redirect as a Symfony `RedirectResponse`, for frameworks that return a response object |

`redirect()` sends the headers and a small HTML page with `echo`; call it in the controller and do not send other output. In frameworks, return `getRedirectResponse()`, or redirect to `getRedirectUrl()` with the framework's own redirect.

The endpoint is `https://ib.slsp.sk/epayment/epayment/epayment.xml`, or the simulator in [test mode](./test-mode); `PurchaseRequest::getEndpoint()` returns it without the query string.

## Choosing the symbols

- **`vs`** is how the return finds its order, and `completePurchase()` checks it. Make it unique per order (and per payment attempt, if your accounting needs that), digits only, up to 10.
- **`ss`** is required by the gateway. Use whatever your accounting expects (a customer number, an invoice year), or a fixed value; pass it to `completePurchase()` too, so the return is checked against it.
- **`param`** is free text, signed in both directions and returned unchanged. It is a convenient place for your own order reference, but the gateway does not check it: compare it yourself if you rely on it.

## Signing a string yourself

`PurchaseRequest::generateSignature(string $data)` returns the 3DES signature of any string with the request's `sharedSecret`, the same as `new Des3Sign()->sign($data, $secret)` ([Signatures](./signatures)).
