<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Exception;

/**
 * An invalid argument, for example a shared secret that is not base64.
 */
final class InvalidArgumentException extends \InvalidArgumentException implements SporoPayException {}
