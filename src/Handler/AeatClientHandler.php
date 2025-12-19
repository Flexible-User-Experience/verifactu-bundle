<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

use Flux\VerifactuBundle\Dto\ComputerSystemDto;
use Flux\VerifactuBundle\Factory\ComputerSystemFactory;

final readonly class AeatClientHandler
{
    public function __construct(
        private array $computerSystemConfig,
        private ComputerSystemFactory $computerSystemFactory,
    ) {
    }

    public function getTest(): string
    {
        $computerSystemDto = new ComputerSystemDto(
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
        $validatedComputerSystem = $this->computerSystemFactory->create($computerSystemDto);

        return $validatedComputerSystem->getName();
    }
}
