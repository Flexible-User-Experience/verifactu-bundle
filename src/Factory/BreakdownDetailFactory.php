<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\BreakdownDetailInterface;
use Flux\VerifactuBundle\Dto\BreakdownDetailDto;

final readonly class BreakdownDetailFactory
{
    public function create(BreakdownDetailInterface $input): BreakdownDetailDto
    {
        return new BreakdownDetailDto(
            taxType: $input->getTaxType(),
            regimeType: $input->getRegimeType(),
            operationType: $input->getOperationType(),
            baseAmount: $input->getBaseAmount(),
            taxRate: $input->getTaxRate(),
            taxAmount: $input->getTaxAmount(),
            surchargeRate: $input->getSurchargeRate(),
            surchargeAmount: $input->getSurchargeAmount(),
        );
    }
}
