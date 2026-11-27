<?php

declare(strict_types=1);

namespace Omnipay\SporoPay\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\SporoPay\Sign\Des3Sign;
use Omnipay\SporoPay\StringParameterTrait;

final class CompletePurchaseRequest extends AbstractRequest
{
    use StringParameterTrait;

    /**
     * Query parameters covered by SIGN2, in signing order.
     */
    private const array SIGNED_FIELDS = [
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
     * Verifies the bank's return request and that it belongs to the expected order.
     *
     * SIGN2 covers every field of the return, so after the signature check the variable
     * symbol, the amount, the currency and (when given) the specific symbol are compared
     * with the order being completed.
     *
     * @return array{RES: string, VS: string, SS: string, AMOUNT: string, CURRENCY: string, REAL: string}
     * @throws InvalidRequestException when vs or amount is missing, the signature is invalid (also when a
     *                                 signed field contains the separator ';'), or the return belongs to
     *                                 another payment
     */
    #[\Override]
    public function getData(): array
    {
        $expectedVs = $this->getVs();
        if ($expectedVs === null || $expectedVs === '') {
            throw new InvalidRequestException('The vs parameter is required');
        }
        $this->validate('amount');
        $expectedAmount = $this->getAmount(); // formatted with two decimals, like purchase() sends it

        $fields = array_map($this->queryString(...), self::SIGNED_FIELDS);
        // SIGN2 covers the fields joined with ';': a field containing ';' could be split differently
        // (for example moving a part of `param` into `result`), so such a return is never accepted
        foreach ($fields as $field) {
            if (str_contains($field, ';')) {
                throw new InvalidRequestException('incorect signature');
            }
        }
        $data = implode(';', $fields);
        $sign = new Des3Sign();

        if (!hash_equals($sign->sign($data, $this->getSharedSecret() ?? ''), $this->queryString('SIGN2'))) {
            throw new InvalidRequestException('incorect signature');
        }

        $vs = $this->queryString('vs');
        if ($vs !== $expectedVs) {
            throw new InvalidRequestException('Variable symbol mismatch');
        }

        $amount = $this->queryString('suma');
        if ($amount !== $expectedAmount) {
            throw new InvalidRequestException('Amount mismatch');
        }

        $currency = $this->queryString('mena');
        if ($currency !== PurchaseRequest::CURRENCY) {
            throw new InvalidRequestException('Currency mismatch');
        }

        $ss = $this->queryString('ss');
        $expectedSs = $this->getSs();
        if ($expectedSs !== null && $ss !== $expectedSs) {
            throw new InvalidRequestException('Specific symbol mismatch');
        }

        return [
            'RES' => $this->queryString('result'),
            'VS' => $vs,
            'SS' => $ss,
            'AMOUNT' => $amount,
            'CURRENCY' => $currency,
            'REAL' => $this->queryString('real'),
        ];
    }

    #[\Override]
    public function send(): CompletePurchaseResponse
    {
        return $this->sendData($this->getData());
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

    public function setSharedSecret(#[\SensitiveParameter] ?string $value): static
    {
        $this->setParameter('sharedSecret', $value);
        return $this;
    }

    /**
     * The variable symbol of the order being completed; the returned vs must be equal to it.
     */
    public function getVs(): ?string
    {
        return $this->getStringParameter('vs');
    }

    public function setVs(?string $value): static
    {
        $this->setParameter('vs', $value);
        return $this;
    }

    /**
     * The specific symbol sent in purchase(); when set, the returned ss must be equal to it.
     */
    public function getSs(): ?string
    {
        return $this->getStringParameter('ss');
    }

    public function setSs(?string $value): static
    {
        $this->setParameter('ss', $value);
        return $this;
    }

    /**
     * A query parameter of the bank's return request; '' when it is missing or not a string.
     */
    private function queryString(string $key): string
    {
        $value = $this->httpRequest->query->all()[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
