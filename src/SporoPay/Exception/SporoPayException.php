<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Exception;

use Omnipay\Common\Exception\OmnipayException;

/**
 * Implemented by every exception this package throws itself.
 *
 * Invalid requests and bank returns are reported with Omnipay's own
 * `InvalidRequestException`; catch `OmnipayException` to handle both.
 */
interface SporoPayException extends OmnipayException, \Throwable {}
