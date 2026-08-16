<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Tests\Transformer;

use FlexibleUx\VerifactuBundle\Dto\BreakdownDetailDto;
use FlexibleUx\VerifactuBundle\Transformer\BreakdownDetailTransformer;
use josemmo\Verifactu\Models\Records\OperationType;
use josemmo\Verifactu\Models\Records\RegimeType;
use josemmo\Verifactu\Models\Records\TaxType;
use PHPUnit\Framework\TestCase;

final class BreakdownDetailTransformerTest extends TestCase
{
    private BreakdownDetailTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new BreakdownDetailTransformer();
    }

    public function testTransformDtoToModelMapsAllFields(): void
    {
        $dto = new BreakdownDetailDto(
            taxType: TaxType::IVA,
            regimeType: RegimeType::C18,
            operationType: OperationType::Subject,
            baseAmount: '100.00',
            taxRate: '21.00',
            taxAmount: '21.00',
            surchargeRate: '5.20',
            surchargeAmount: '5.20'
        );
        $model = $this->transformer->transformDtoToModel($dto);
        $this->assertSame(TaxType::IVA, $model->taxType);
        $this->assertSame(RegimeType::C18, $model->regimeType);
        $this->assertSame(OperationType::Subject, $model->operationType);
        $this->assertSame('100.00', $model->baseAmount);
        $this->assertSame('21.00', $model->taxRate);
        $this->assertSame('21.00', $model->taxAmount);
        $this->assertSame('5.20', $model->surchargeRate);
        $this->assertSame('5.20', $model->surchargeAmount);
    }

    public function testEquivalenceSurchargeRegimeModelIsValid(): void
    {
        $dto = new BreakdownDetailDto(
            taxType: TaxType::IVA,
            regimeType: RegimeType::C18,
            operationType: OperationType::Subject,
            baseAmount: '100.00',
            taxRate: '21.00',
            taxAmount: '21.00',
            surchargeRate: '5.20',
            surchargeAmount: '5.20'
        );
        $this->expectNotToPerformAssertions();
        $this->transformer->transformDtoToModel($dto)->validate();
    }
}
