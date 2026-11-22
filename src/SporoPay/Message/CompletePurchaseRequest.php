<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\SporoPay\Sign\Des3Sign;
use Omnipay\SporoPay\StringParameterTrait;

class CompletePurchaseRequest extends AbstractRequest
{
    use StringParameterTrait;

    /**
     * Query parameters covered by SIGN2, in signing order.
     */
    private const SIGNED_FIELDS = [
        'u_predcislo',
        'u_cislo',
        'u_kbanky',
        'pu_predcislo',
        'pu_cislo',
        'pu_kbanky',
        'suma',
        'mena',
        'vs',
        'ss',
        'url',
        'param',
        'result',
        'real',
    ];

    /**
     * @return array{RES: string, VS: string}
     */
    #[\Override]
    public function getData(): array
    {
        $data = implode(';', array_map(self::queryString(...), self::SIGNED_FIELDS));
        $sign = new Des3Sign();

        if (!hash_equals($sign->sign($data, $this->getSharedSecret() ?? ''), self::queryString('SIGN2'))) {
            throw new InvalidRequestException('incorect signature');
        }

        return [
            'RES' => self::queryString('result'),
            'VS' => self::queryString('vs'),
        ];
    }

    #[\Override]
    public function sendData(mixed $data): CompletePurchaseResponse
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }

    public function getSharedSecret(): ?string
    {
        return $this->getStringParameter('sharedSecret');
    }

    public function setSharedSecret(?string $value): static
    {
        $this->setParameter('sharedSecret', $value);
        return $this;
    }

    private static function queryString(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
