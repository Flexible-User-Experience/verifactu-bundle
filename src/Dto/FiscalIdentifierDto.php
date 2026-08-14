<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Dto;

use Flux\VerifactuBundle\Contract\FiscalIdentifierInterface;
use Flux\VerifactuBundle\Validator\Constraints\NifOrCif;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FiscalIdentifierDto implements FiscalIdentifierInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 120)]
        private string $name,
        #[Assert\NotBlank]
        #[NifOrCif]
        private string $nif,
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
