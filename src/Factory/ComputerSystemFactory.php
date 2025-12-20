<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\ComputerSystemInterface;
use Flux\VerifactuBundle\Dto\ComputerSystemDto;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\ComputerSystem;

final readonly class ComputerSystemFactory
{
    public function create(ComputerSystemInterface $input): ComputerSystemDto
    {
        return new ComputerSystemDto(
            vendorName: ContractsValidator::tt($input->getVendorName()),
            vendorNif: ContractsValidator::tt($input->getVendorNif(), 9),
            name: ContractsValidator::tt($input->getName(), 30),
            id: ContractsValidator::tt($input->getId(), 2),
            version: ContractsValidator::tt($input->getVersion(), 50),
            installationNumber: ContractsValidator::tt($input->getInstallationNumber(), 100),
            onlySupportsVerifactu: $input->isOnlySupportsVerifactu(),
            supportsMultipleTaxpayers: $input->isSupportsMultipleTaxpayers(),
            hasMultipleTaxpayers: $input->isHasMultipleTaxpayers(),
        );
    }

    public function transformDtoToModel(ComputerSystemInterface $dto): ComputerSystem
    {
        $system = new ComputerSystem();
        $system->vendorName = $dto->getVendorName();
        $system->vendorNif = $dto->getVendorNif();
        $system->name = $dto->getName();
        $system->id = $dto->getId();
        $system->version = $dto->getVersion();
        $system->installationNumber = $dto->getInstallationNumber();
        $system->onlySupportsVerifactu = $dto->isOnlySupportsVerifactu();
        $system->supportsMultipleTaxpayers = $dto->isSupportsMultipleTaxpayers();
        $system->hasMultipleTaxpayers = $dto->isHasMultipleTaxpayers();

        return $system;
    }
}
