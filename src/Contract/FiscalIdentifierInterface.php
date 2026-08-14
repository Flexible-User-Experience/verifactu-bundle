<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Contract;

interface FiscalIdentifierInterface extends ValidatableInterface
{
    public function getName(): string;

    public function getNif(): string;
}
