<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Tests\Dto;

use FlexibleUx\VerifactuBundle\Dto\FiscalIdentifierDto;
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

    public function testNifMustBeAValidNifOrCif(): void
    {
        $dto = new FiscalIdentifierDto(
            name: 'Empresa SL',
            nif: '123456789'
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('nif', $violations[0]->getPropertyPath());
    }
}
