<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Dto;

use Flux\VerifactuBundle\Contract\ComputerSystemInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ComputerSystemDto implements ComputerSystemInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 120)]
        private string $vendorName,
        #[Assert\NotBlank]
        #[Assert\Length(exactly: 9)]
        private string $vendorNif, // TODO apply a better assert "A00000000" | "00000000A"
        #[Assert\NotBlank]
        #[Assert\Length(max: 30)]
        private string $name,
        #[Assert\NotBlank]
        #[Assert\Length(max: 2)]
        private string $id,
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        private string $version,
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        private string $installationNumber,
        #[Assert\NotNull]
        #[Assert\Type('boolean')]
        private bool $onlySupportsVerifactu,
        #[Assert\NotNull]
        #[Assert\Type('boolean')]
        private bool $supportsMultipleTaxpayers,
        #[Assert\NotNull]
        #[Assert\Type('boolean')]
        private bool $hasMultipleTaxpayers
    ) {
    }

    public function getVendorName(): string
    {
        return $this->vendorName;
    }

    public function getVendorNif(): string
    {
        return $this->vendorNif;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getInstallationNumber(): string
    {
        return $this->installationNumber;
    }

    public function isOnlySupportsVerifactu(): bool
    {
        return $this->onlySupportsVerifactu;
    }

    public function isSupportsMultipleTaxpayers(): bool
    {
        return $this->supportsMultipleTaxpayers;
    }

    public function isHasMultipleTaxpayers(): bool
    {
        return $this->hasMultipleTaxpayers;
    }
}
