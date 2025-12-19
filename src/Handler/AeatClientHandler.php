<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

use Flux\VerifactuBundle\Factory\ComputerSystemFactory;

final class AeatClientHandler
{
    private ComputerSystemFactory $computerSystemFactory;

    public function __construct(
        private readonly array $computerSystemConfig,
    ) {
    }

    public function __invoke(): void
    {
        $this->computerSystemFactory = new ComputerSystemFactory();
    }

    public function getTest(): string
    {
        return $this->isProdEnvironment ? 'true' : 'false';
    }
}
