<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract readonly class BaseFactory
{
    public function __construct(
        protected ValidatorInterface $validator,
    ) {
    }

    /**
     * Trim & Truncate.
     */
    protected function tt(string $value, int $maxLength = 120): string
    {
        return mb_substr(trim($value), 0, $maxLength);
    }
}
