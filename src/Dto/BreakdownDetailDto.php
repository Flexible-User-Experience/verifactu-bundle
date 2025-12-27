<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Dto;

use Flux\VerifactuBundle\Contract\BreakdownDetailInterface;
use josemmo\Verifactu\Models\Records\OperationType;
use josemmo\Verifactu\Models\Records\RegimeType;
use josemmo\Verifactu\Models\Records\TaxType;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class BreakdownDetailDto implements BreakdownDetailInterface
{
    public function __construct(
        #[Assert\NotBlank]
        private TaxType $taxType,
        #[Assert\NotBlank]
        private RegimeType $regimeType,
        #[Assert\NotBlank]
        private OperationType $operationType,
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^-?\d{1,12}\.\d{2}$/')]
        private string $baseAmount,
        #[Assert\Regex(pattern: '/^\d{1,3}\.\d{2}$/')]
        private ?string $taxRate = null,
        #[Assert\Regex(pattern: '/^-?\d{1,12}\.\d{2}$/')]
        private ?string $taxAmount = null,
        #[Assert\Regex(pattern: '/^\d{1,3}\.\d{2}$/')]
        private ?string $surchargeRate = null,
        #[Assert\Regex(pattern: '/^-?\d{1,12}\.\d{2}$/')]
        private ?string $surchargeAmount = null,
    ) {
    }

    public function getTaxType(): TaxType
    {
        return $this->taxType;
    }

    public function getRegimeType(): RegimeType
    {
        return $this->regimeType;
    }

    public function getOperationType(): OperationType
    {
        return $this->operationType;
    }

    public function getBaseAmount(): string
    {
        return $this->baseAmount;
    }

    public function getTaxRate(): ?string
    {
        return $this->taxRate;
    }

    public function getTaxAmount(): ?string
    {
        return $this->taxAmount;
    }

    public function getSurchargeRate(): ?string
    {
        return $this->surchargeRate;
    }

    public function getSurchargeAmount(): ?string
    {
        return $this->surchargeAmount;
    }
}
