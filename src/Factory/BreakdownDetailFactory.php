<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\BreakdownDetailInterface;
use Flux\VerifactuBundle\Dto\BreakdownDetailDto;
use Flux\VerifactuBundle\Transformer\BreakdownDetailTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
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
