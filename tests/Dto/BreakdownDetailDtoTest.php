<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Dto;

use Flux\VerifactuBundle\Dto\BreakdownDetailDto;
use josemmo\Verifactu\Models\Records\OperationType;
use josemmo\Verifactu\Models\Records\RegimeType;
use josemmo\Verifactu\Models\Records\TaxType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BreakdownDetailDtoTest extends TestCase
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
        $dto = new BreakdownDetailDto(
            taxType: TaxType::IVA,
            regimeType: RegimeType::C01,
            operationType: OperationType::Subject,
            baseAmount: '100.00',
            taxRate: '21.00',
            taxAmount: '21.00',
            surchargeRate: null,
            surchargeAmount: null
        );

        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testBaseAmountInvalidFormat(): void
    {
        $dto = new BreakdownDetailDto(
            taxType: TaxType::IVA,
            regimeType: RegimeType::C01,
            operationType: OperationType::Subject,
            baseAmount: '100',
            taxRate: '21.00',
            taxAmount: '21.00',
            surchargeRate: null,
            surchargeAmount: null
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('baseAmount', $violations[0]->getPropertyPath());
    }

    public function testTaxRateInvalidFormat(): void
    {
        $dto = new BreakdownDetailDto(
            taxType: TaxType::IVA,
            regimeType: RegimeType::C01,
            operationType: OperationType::Subject,
            baseAmount: '100.00',
            taxRate: '21',
            taxAmount: '21.00',
            surchargeRate: null,
            surchargeAmount: null
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('taxRate', $violations[0]->getPropertyPath());
    }

    public function testNegativeBaseAmountIsValid(): void
    {
        $dto = new BreakdownDetailDto(
            taxType: TaxType::IVA,
            regimeType: RegimeType::C01,
            operationType: OperationType::Subject,
            baseAmount: '-100.00',
            taxRate: '21.00',
            taxAmount: '-21.00',
            surchargeRate: '5.20',
            surchargeAmount: '-5.20'
        );
        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }
}
