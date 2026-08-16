<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Factory;

use FlexibleUx\VerifactuBundle\Dto\ComputerSystemDto;
use FlexibleUx\VerifactuBundle\Transformer\ComputerSystemTransformer;
use FlexibleUx\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\ComputerSystem;

final readonly class ComputerSystemFactory
{
    public function __construct(
        private array $computerSystemConfig,
        private ComputerSystemTransformer $computerSystemTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedComputerSystemModel(): ComputerSystem
    {
        $validatedComputerSystemDto = $this->makeValidatedComputerSystemDto();
        $computerSystemModel = $this->computerSystemTransformer->transformDtoToModel($validatedComputerSystemDto);
        $computerSystemModel->validate();

        return $computerSystemModel;
    }

    private function makeValidatedComputerSystemDto(): ComputerSystemDto
    {
        $computerSystemDto = $this->computerSystemTransformer->transformComputerSystemConfigToDto($this->computerSystemConfig);
        $this->validator->validate($computerSystemDto);

        return $computerSystemDto;
    }
}
