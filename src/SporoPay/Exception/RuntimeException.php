<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Exception;

/**
 * A failure outside the caller's control, for example OpenSSL not computing the signature.
 */
final class RuntimeException extends \RuntimeException implements SporoPayException {}
