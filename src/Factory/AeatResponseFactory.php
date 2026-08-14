<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Factory;

use FlexibleUx\VerifactuBundle\Contract\AeatResponseInterface;
use FlexibleUx\VerifactuBundle\Dto\AeatResponseDto;
use FlexibleUx\VerifactuBundle\Transformer\AeatResponseTransformer;
use FlexibleUx\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Responses\AeatResponse;

final readonly class AeatResponseFactory
{
    public function __construct(
        private AeatResponseTransformer $aeatResponseTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedAeatResponseDtoFromModel(AeatResponse $model): AeatResponseInterface
    {
        $validatedAeatResponseDto = $this->aeatResponseTransformer->transformModelToDto($model);
        $this->validator->validate($validatedAeatResponseDto);

        return $validatedAeatResponseDto;
    }

    public function makeValidatedAeatResponseDtoFromInterface(AeatResponseInterface $input): AeatResponseDto
    {
        $validatedAeatResponseDto = $this->aeatResponseTransformer->transformInterfaceToDto($input);
        $this->validator->validate($validatedAeatResponseDto);

        return $validatedAeatResponseDto;
    }

    public function getJsonArrayFromAeatResponseDto(AeatResponseDto $dto): array
    {
        return $this->aeatResponseTransformer->transformDtoToArray($dto);
    }

    public function getJsonStringFromAeatResponseDto(AeatResponseDto $dto): string
    {
        return $this->aeatResponseTransformer->transformDtoToJson($dto);
    }
}
