<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Sign;

use Omnipay\SporoPay\Exception\InvalidArgumentException;
use Omnipay\SporoPay\Exception\RuntimeException;

final readonly class Des3Sign
{
    /**
     * @throws InvalidArgumentException when the secret is empty, not base64, or not a 16- or 24-byte key
     *                                  (OpenSSL would pad a shorter key with zero bytes, so an empty secret
     *                                  would sign with an all-zero key anyone can compute)
     * @throws RuntimeException when OpenSSL cannot compute the signature
     */
    public function sign(string $input, #[\SensitiveParameter] string $secret): string
    {
        if (trim($secret) === '') {
            throw new InvalidArgumentException('Shared secret must not be empty');
        }

        $ssBytes = base64_decode($secret, true);
        if ($ssBytes === false) {
            throw new InvalidArgumentException('Shared secret must be base64 encoded');
        }
        // two-key (K1 K2, used as K1 K2 K1) or three-key 3DES
        $keyLength = strlen($ssBytes);
        if ($keyLength !== 16 && $keyLength !== 24) {
            throw new InvalidArgumentException('Shared secret must be a 16- or 24-byte 3DES key');
        }

        $bytesHash = sha1($input, true);

        while (strlen($bytesHash) < 24) {
            $bytesHash .= chr(0xFF);
        }

        $key = $ssBytes . substr($ssBytes, 0, 8);

        $iv = "\x00\x00\x00\x00\x00\x00\x00\x00";

        $signatureBytes = @openssl_encrypt($bytesHash, 'DES-EDE3-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
        if ($signatureBytes === false) {
            $error = openssl_error_string();
            throw new RuntimeException('Unable to compute the DES-EDE3-CBC signature: ' . ($error !== false ? $error : 'unknown OpenSSL error'));
        }

        return base64_encode($signatureBytes);
    }
}
