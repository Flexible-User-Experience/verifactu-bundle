<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Contract;

interface ComputerSystemInterface extends ValidatableInterface
{
    public function getVendorName(): string;

    public function getVendorNif(): string;

    public function getName(): string;

    public function getId(): string;

    public function getVersion(): string;

    public function getInstallationNumber(): string;

    public function isOnlySupportsVerifactu(): bool;

    public function isSupportsMultipleTaxpayers(): bool;

    public function isHasMultipleTaxpayers(): bool;
}
