<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Dto;

use Flux\VerifactuBundle\Dto\FiscalIdentifierDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class FiscalIdentifierDtoTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidDto(): void
    {
        $dto = new FiscalIdentifierDto(
            name: 'Empresa SL',
            nif: 'A00000000'
        );
        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }
}
