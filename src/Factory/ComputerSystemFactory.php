<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\ComputerSystemInterface;
use Flux\VerifactuBundle\Dto\ComputerSystemDto;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ComputerSystemFactory
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function create(ComputerSystemInterface $input): ComputerSystemInterface
    {
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($input, $violations);
        }

        return new ComputerSystemDto(
            vendorName: $input->getVendorName(),
            vendorNif: $input->getVendorNif(),
            name: $input->getName(),
            id: $input->getId(),
            version: $input->getVersion(),
            installationNumber: $input->getInstallationNumber(),
            onlySupportsVerifactu: $input->isOnlySupportsVerifactu(),
            supportsMultipleTaxpayers: $input->isSupportsMultipleTaxpayers(),
            hasMultipleTaxpayers: $input->isHasMultipleTaxpayers(),
        );
    }
}
