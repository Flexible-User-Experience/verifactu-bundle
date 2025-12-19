<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

final readonly class TestHandler
{
    public function __construct(
        private bool $isProdEnvironment,
    ) {
    }

    public function getTest(): string
    {
        return $this->isProdEnvironment ? 'true' : 'false';
    }
}
