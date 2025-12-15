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

    private function tt(string $value, int $maxLength = 120): string
    {
        return mb_substr(trim($value), 0, $maxLength);
    }
}
