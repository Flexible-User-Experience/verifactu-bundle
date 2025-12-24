<?php

namespace Flux\VerifactuBundle\Validator;

use Flux\VerifactuBundle\Contract\ValidatableInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class ContractsValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function validate(ValidatableInterface $input): void
    {
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($input, $violations);
        }
    }
}
