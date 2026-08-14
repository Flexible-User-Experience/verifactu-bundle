<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Contract;

use josemmo\Verifactu\Models\Records\ForeignIdType;

interface ForeignFiscalIdentifierInterface extends ValidatableInterface
{
    public function getName(): string;

    /**
     * ISO 3166-1 alpha-2 country code.
     */
    public function getCountry(): string;

    public function getType(): ForeignIdType;

    public function getValue(): string;
}
