<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Factory;

use FlexibleUx\VerifactuBundle\Contract\BreakdownDetailInterface;
use FlexibleUx\VerifactuBundle\Dto\BreakdownDetailDto;
use FlexibleUx\VerifactuBundle\Transformer\BreakdownDetailTransformer;
use FlexibleUx\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\BreakdownDetails;

final readonly class BreakdownDetailFactory
{
    public function __construct(
        private BreakdownDetailTransformer $breakdownDetailTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedBreakdownDetailDtoFromInterface(BreakdownDetailInterface $input): BreakdownDetailDto
    {
        $breakdownDetailDto = $this->breakdownDetailTransformer->transformInterfaceToDto($input);
        $this->validator->validate($breakdownDetailDto);

        return $breakdownDetailDto;
    }

    public function makeValidatedBreakdownDetailModelFromDto(BreakdownDetailDto $dto): BreakdownDetails
    {
        $breakdownDetailModel = $this->breakdownDetailTransformer->transformDtoToModel($dto);
        $breakdownDetailModel->validate();

        return $breakdownDetailModel;
    }
}
