<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Transformer;

use FlexibleUx\VerifactuBundle\Contract\BreakdownDetailInterface;
use FlexibleUx\VerifactuBundle\Dto\BreakdownDetailDto;
use josemmo\Verifactu\Models\Records\BreakdownDetails;

final readonly class BreakdownDetailTransformer extends BaseTransformer
{
    public function transformInterfaceToDto(BreakdownDetailInterface $input): BreakdownDetailDto
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

    public function transformDtoToModel(BreakdownDetailDto $dto): BreakdownDetails
    {
        $detail = new BreakdownDetails();
        $detail->taxType = $dto->getTaxType();
        $detail->regimeType = $dto->getRegimeType();
        $detail->operationType = $dto->getOperationType();
        $detail->baseAmount = $dto->getBaseAmount();
        $detail->taxRate = $dto->getTaxRate();
        $detail->taxAmount = $dto->getTaxAmount();
        $detail->surchargeRate = $dto->getSurchargeRate();
        $detail->surchargeAmount = $dto->getSurchargeAmount();

        return $detail;
    }
}
