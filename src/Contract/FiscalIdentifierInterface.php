<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Contract;

interface FiscalIdentifierInterface
{
    public function getName(): string;

    public function getNif(): string;
}
