<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Dto;

use Flux\VerifactuBundle\Contract\FiscalIdentifierInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FiscalIdentifierDto implements FiscalIdentifierInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 120)]
        private string $name,
        #[Assert\NotBlank]
        #[Assert\Length(exactly: 9)]
        private string $nif,
        // TODO apply a better assert "A00000000" | "00000000A"
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNif(): string
    {
        return $this->nif;
    }
}
