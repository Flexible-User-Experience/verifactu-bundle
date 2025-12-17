<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\BreakdownDetailInterface;
use Flux\VerifactuBundle\Dto\BreakdownDetailDto;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class BreakdownDetailFactory extends BaseFactory
{
    public function create(BreakdownDetailInterface $input): BreakdownDetailInterface
    {
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($input, $violations);
        }

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
