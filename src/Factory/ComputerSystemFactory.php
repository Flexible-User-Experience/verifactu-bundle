<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Dto\ComputerSystemDto;
use Flux\VerifactuBundle\Transformer\ComputerSystemTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\ComputerSystem;

final readonly class ComputerSystemFactory
{
    public function __construct(
        private array $computerSystemConfig,
        private ComputerSystemTransformer $computerSystemTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedComputerSystemDto(): ComputerSystemDto
    {
        $computerSystemDto = $this->makeComputerSystemDto();
        $this->validator->validate($computerSystemDto);

        return $computerSystemDto;
    }

    public function makeComputerSystemModelFromDto(ComputerSystemDto $input): ComputerSystem
    {
        return $this->computerSystemTransformer->transformDtoToModel($input);
    }

    private function makeComputerSystemDto(): ComputerSystemDto
    {
        return new ComputerSystemDto(
            vendorName: $this->computerSystemConfig['vendor_name'],
            vendorNif: $this->computerSystemConfig['vendor_nif'],
            name: $this->computerSystemConfig['name'],
            id: $this->computerSystemConfig['id'],
            version: $this->computerSystemConfig['version'],
            installationNumber: $this->computerSystemConfig['installation_number'],
            onlySupportsVerifactu: $this->computerSystemConfig['only_supports_verifactu'],
            supportsMultipleTaxpayers: $this->computerSystemConfig['supports_multiple_taxpayers'],
            hasMultipleTaxpayers: $this->computerSystemConfig['has_multiple_taxpayers'],
        );
    }
}
