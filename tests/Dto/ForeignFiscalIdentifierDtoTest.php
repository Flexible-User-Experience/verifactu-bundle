<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Tests\Dto;

use FlexibleUx\VerifactuBundle\Dto\ForeignFiscalIdentifierDto;
use josemmo\Verifactu\Models\Records\ForeignIdType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ForeignFiscalIdentifierDtoTest extends TestCase
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
        $dto = new ForeignFiscalIdentifierDto(
            name: 'ACME GmbH',
            country: 'DE',
            type: ForeignIdType::VAT,
            value: 'DE123456789'
        );
        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testNameCannotBeBlank(): void
    {
        $dto = new ForeignFiscalIdentifierDto(
            name: '',
            country: 'DE',
            type: ForeignIdType::VAT,
            value: 'DE123456789'
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('name', $violations[0]->getPropertyPath());
    }

    public function testCountryMustBeTwoUppercaseLetters(): void
    {
        $dto = new ForeignFiscalIdentifierDto(
            name: 'ACME GmbH',
            country: 'de',
            type: ForeignIdType::VAT,
            value: 'DE123456789'
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('country', $violations[0]->getPropertyPath());
    }

    public function testValueMustNotExceedMaxLength(): void
    {
        $dto = new ForeignFiscalIdentifierDto(
            name: 'ACME GmbH',
            country: 'DE',
            type: ForeignIdType::VAT,
            value: 'DE1234567890123456789'
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('value', $violations[0]->getPropertyPath());
    }
}
