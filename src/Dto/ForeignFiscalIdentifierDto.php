<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Dto;

use Flux\VerifactuBundle\Contract\ForeignFiscalIdentifierInterface;
use josemmo\Verifactu\Models\Records\ForeignIdType;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ForeignFiscalIdentifierDto implements ForeignFiscalIdentifierInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 120)]
        private string $name,
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[A-Z]{2}$/')]
        private string $country,
        #[Assert\NotBlank]
        private ForeignIdType $type,
        #[Assert\NotBlank]
        #[Assert\Length(max: 20)]
        private string $value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getType(): ForeignIdType
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
