<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Sign;

class Des3Sign
{
    public function sign(string $input, string $secret): string
    {
        $bytesHash = sha1($input, true);

        while (strlen($bytesHash) < 24) {
            $bytesHash .= chr(0xFF);
        }

        $ssBytes = base64_decode($secret, true);
        if ($ssBytes === false) {
            throw new \InvalidArgumentException('Shared secret must be base64 encoded');
        }
        $key = $ssBytes . substr($ssBytes, 0, 8);

        $iv = str_repeat(chr(0x00), 8);

        $signatureBytes = @openssl_encrypt($bytesHash, 'DES-EDE3-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
        if ($signatureBytes === false) {
            $error = openssl_error_string();
            throw new \RuntimeException('Unable to compute the DES-EDE3-CBC signature: ' . ($error !== false ? $error : 'unknown OpenSSL error'));
        }

        return base64_encode($signatureBytes);
    }
}
