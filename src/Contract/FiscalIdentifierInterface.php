<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Contract;

interface FiscalIdentifierInterface extends ValidatableInterface
{
    public function getName(): string;

    public function getNif(): string;
}
