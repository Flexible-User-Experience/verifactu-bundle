<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Contract;

use josemmo\Verifactu\Models\Records\OperationType;
use josemmo\Verifactu\Models\Records\RegimeType;
use josemmo\Verifactu\Models\Records\TaxType;

interface BreakdownDetailInterface
{
    public function getTaxType(): TaxType;

    public function getRegimeType(): RegimeType;

    public function getOperationType(): OperationType;

    public function getBaseAmount(): string;

    public function getTaxRate(): ?string;

    public function getTaxAmount(): ?string;

    public function getSurchargeRate(): ?string;

    public function getSurchargeAmount(): ?string;
}
