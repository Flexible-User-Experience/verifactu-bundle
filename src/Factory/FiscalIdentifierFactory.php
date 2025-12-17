<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\FiscalIdentifierInterface;
use Flux\VerifactuBundle\Dto\FiscalIdentifierDto;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class FiscalIdentifierFactory extends BaseFactory
{
    public function create(FiscalIdentifierInterface $input): FiscalIdentifierInterface
    {
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($input, $violations);
        }

        return new FiscalIdentifierDto(
            name: $this->tt($input->getName(), 120),
            nif: $this->tt($input->getNif(), 9),
        );
    }
}
