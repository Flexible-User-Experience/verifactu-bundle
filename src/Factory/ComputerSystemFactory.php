<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\ComputerSystemInterface;
use Flux\VerifactuBundle\Dto\ComputerSystemDto;
use josemmo\Verifactu\Models\ComputerSystem;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ComputerSystemFactory extends BaseFactory
{
    public function create(ComputerSystemInterface $input): ComputerSystemInterface
    {
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($input, $violations);
        }

        return new ComputerSystemDto(
            vendorName: $this->tt($input->getVendorName()),
            vendorNif: $this->tt($input->getVendorNif(), 9),
            name: $this->tt($input->getName(), 30),
            id: $this->tt($input->getId(), 2),
            version: $this->tt($input->getVersion(), 50),
            installationNumber: $this->tt($input->getInstallationNumber(), 100),
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
