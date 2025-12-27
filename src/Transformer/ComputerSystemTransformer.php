<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Transformer;

use Flux\VerifactuBundle\Dto\ComputerSystemDto;
use josemmo\Verifactu\Models\ComputerSystem;

final readonly class ComputerSystemTransformer extends BaseTransformer
{
    public function transformComputerSystemConfigToDto(array $input): ComputerSystemDto
    {
        return new ComputerSystemDto(
            vendorName: self::tt($input['vendor_name']),
            vendorNif: self::tt($input['vendor_nif'], 9),
            name: self::tt($input['name'], 30),
            id: self::tt($input['id'], 2),
            version: self::tt($input['version'], 50),
            installationNumber: self::tt($input['installation_number'], 100),
            onlySupportsVerifactu: $input['only_supports_verifactu'],
            supportsMultipleTaxpayers: $input['supports_multiple_taxpayers'],
            hasMultipleTaxpayers: $input['has_multiple_taxpayers'],
        );
    }

    public function transformDtoToModel(ComputerSystemDto $dto): ComputerSystem
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
