<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class TestHandler
{
    public function __construct(
        #[Autowire(param: '%kernel.default_locale%')]
        private readonly string $isProdEnvironment
    ) {
    }

    public function getTest(): string
    {
        return $this->isProdEnvironment;
    }
}
