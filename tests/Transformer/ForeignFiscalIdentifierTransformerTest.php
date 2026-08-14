<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Transformer;

use Flux\VerifactuBundle\Dto\ForeignFiscalIdentifierDto;
use Flux\VerifactuBundle\Transformer\ForeignFiscalIdentifierTransformer;
use josemmo\Verifactu\Models\Records\ForeignIdType;
use PHPUnit\Framework\TestCase;

final class ForeignFiscalIdentifierTransformerTest extends TestCase
{
    private ForeignFiscalIdentifierTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ForeignFiscalIdentifierTransformer();
    }

    public function testTransformDtoToModelMapsAllFields(): void
    {
        $dto = new ForeignFiscalIdentifierDto(
            name: 'ACME GmbH',
            country: 'DE',
            type: ForeignIdType::VAT,
            value: 'DE123456789'
        );
        $model = $this->transformer->transformDtoToModel($dto);
        $this->assertSame('ACME GmbH', $model->name);
        $this->assertSame('DE', $model->country);
        $this->assertSame(ForeignIdType::VAT, $model->type);
        $this->assertSame('DE123456789', $model->value);
    }

    public function testForeignVatRecipientModelIsValid(): void
    {
        $dto = new ForeignFiscalIdentifierDto(
            name: 'ACME GmbH',
            country: 'DE',
            type: ForeignIdType::VAT,
            value: 'DE123456789'
        );
        $this->expectNotToPerformAssertions();
        $this->transformer->transformDtoToModel($dto)->validate();
    }
}
