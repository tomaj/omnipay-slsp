<?php

namespace Omnipay\SporoPay\Sign;

class Des3Sign
{
    public function sign($input, $secret)
    {
        $signature = null;
        
        $bytesHash = sha1($input, true);

        while (strlen($bytesHash) < 24) {
            $bytesHash .= chr(0xFF);
        }

        $ssBytes = base64_decode($secret);
        $key = $ssBytes . substr($ssBytes, 0, 8);

        $iv = chr(0x00);
        $iv .= $iv; // 2
        $iv .= $iv; // 4
        $iv .= $iv; // 8

        $signatureBytes = mcrypt_encrypt(MCRYPT_TRIPLEDES, $key, $bytesHash, MCRYPT_MODE_CBC, $iv);
        $signature = base64_encode($signatureBytes);
        
        return $signature;
    }
}
