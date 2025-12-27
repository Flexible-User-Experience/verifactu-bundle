<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Dto;

use Flux\VerifactuBundle\Dto\ComputerSystemDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ComputerSystemDtoTest extends TestCase
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
        $dto = new ComputerSystemDto(
            vendorName: 'Vendor Name SL',
            vendorNif: 'B12345678',
            name: 'My Software SIF',
            id: '01',
            version: '1.0.0',
            installationNumber: 'INST-001',
            onlySupportsVerifactu: true,
            supportsMultipleTaxpayers: false,
            hasMultipleTaxpayers: false
        );
        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testVendorNameCannotBeBlank(): void
    {
        $dto = new ComputerSystemDto(
            vendorName: '',
            vendorNif: 'B12345678',
            name: 'My Software SIF',
            id: '01',
            version: '1.0.0',
            installationNumber: 'INST-001',
            onlySupportsVerifactu: true,
            supportsMultipleTaxpayers: false,
            hasMultipleTaxpayers: false
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('vendorName', $violations[0]->getPropertyPath());
    }

    public function testVendorNifMustHaveExactLength(): void
    {
        $dto = new ComputerSystemDto(
            vendorName: 'Vendor Name SL',
            vendorNif: 'B123',
            name: 'My Software SIF',
            id: '01',
            version: '1.0.0',
            installationNumber: 'INST-001',
            onlySupportsVerifactu: true,
            supportsMultipleTaxpayers: false,
            hasMultipleTaxpayers: false
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('vendorNif', $violations[0]->getPropertyPath());
    }

    public function testIdMustNotExceedMaxLength(): void
    {
        $dto = new ComputerSystemDto(
            vendorName: 'Vendor Name SL',
            vendorNif: 'B12345678',
            name: 'My Software SIF',
            id: 'ABC',
            version: '1.0.0',
            installationNumber: 'INST-001',
            onlySupportsVerifactu: true,
            supportsMultipleTaxpayers: false,
            hasMultipleTaxpayers: false
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('id', $violations[0]->getPropertyPath());
    }
}
