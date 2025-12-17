<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

final class TestHandler
{
    public function __construct(
        private readonly string $isProdEnvironment
    ) {
    }

    public function getTest(): string
    {
        return $this->isProdEnvironment ? 'true' : 'false';
    }
}
