<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Transformer;

use Flux\VerifactuBundle\Contract\ComputerSystemInterface;
use Flux\VerifactuBundle\Dto\ComputerSystemDto;
use josemmo\Verifactu\Models\ComputerSystem;

final readonly class ComputerSystemTransformer extends BaseTransformer
{
    public function transformInterfaceToModel(ComputerSystemInterface $input): ComputerSystemDto
    {
        return new ComputerSystemDto(
            vendorName: self::tt($input->getVendorName()),
            vendorNif: self::tt($input->getVendorNif(), 9),
            name: self::tt($input->getName(), 30),
            id: self::tt($input->getId(), 2),
            version: self::tt($input->getVersion(), 50),
            installationNumber: self::tt($input->getInstallationNumber(), 100),
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
