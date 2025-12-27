<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Dto\AeatResponseDto;
use Flux\VerifactuBundle\Transformer\AeatResponseTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Responses\AeatResponse;

final readonly class AeatResponseFactory
{
    public function __construct(
        private AeatResponseTransformer $aeatResponseTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedAeatResponseDtoFromModel(AeatResponse $model): AeatResponseDto
    {
        $validatedAeatResponseDto = $this->aeatResponseTransformer->transformModelToDto($model);
        $this->validator->validate($validatedAeatResponseDto);

        return $validatedAeatResponseDto;
    }
}
