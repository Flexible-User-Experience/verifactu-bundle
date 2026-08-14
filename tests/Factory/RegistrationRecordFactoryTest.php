<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Factory;

use Flux\VerifactuBundle\Dto\BreakdownDetailDto;
use Flux\VerifactuBundle\Dto\InvoiceIdentifierDto;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use Flux\VerifactuBundle\Factory\BreakdownDetailFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\ForeignFiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\InvoiceIdentifierFactory;
use Flux\VerifactuBundle\Factory\RegistrationRecordFactory;
use Flux\VerifactuBundle\Transformer\BreakdownDetailTransformer;
use Flux\VerifactuBundle\Transformer\FiscalIdentifierTransformer;
use Flux\VerifactuBundle\Transformer\ForeignFiscalIdentifierTransformer;
use Flux\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use Flux\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\InvoiceType;
use josemmo\Verifactu\Models\Records\OperationType;
use josemmo\Verifactu\Models\Records\RegimeType;
use josemmo\Verifactu\Models\Records\TaxType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class RegistrationRecordFactoryTest extends TestCase
{
    private RegistrationRecordFactory $factory;

    protected function setUp(): void
    {
        $validator = new ContractsValidator(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator()
        );
        $this->factory = new RegistrationRecordFactory(
            new InvoiceIdentifierFactory(new InvoiceIdentifierTransformer(), $validator),
            new BreakdownDetailFactory(new BreakdownDetailTransformer(), $validator),
            new FiscalIdentifierFactory([], new FiscalIdentifierTransformer(), $validator),
            new ForeignFiscalIdentifierFactory(new ForeignFiscalIdentifierTransformer(), $validator),
            new RegistrationRecordTransformer(),
            $validator
        );
    }

    public function testChainedBatchModelsAreLinkedByInvoiceIdentifierAndHash(): void
    {
        $models = $this->factory->makeValidatedChainedRegistrationRecordModelsFromInterfaces([
            $this->makeRecordDto('FA-2026-001'),
            $this->makeRecordDto('FA-2026-002'),
            $this->makeRecordDto('FA-2026-003'),
        ]);
        $this->assertCount(3, $models);
        $this->assertNull($models[0]->previousInvoiceId);
        $this->assertNull($models[0]->previousHash);
        $this->assertSame($models[0]->invoiceId, $models[1]->previousInvoiceId);
        $this->assertSame($models[0]->hash, $models[1]->previousHash);
        $this->assertSame($models[1]->invoiceId, $models[2]->previousInvoiceId);
        $this->assertSame($models[1]->hash, $models[2]->previousHash);
        foreach ($models as $model) {
            $this->assertMatchesRegularExpression('/^[0-9A-F]{64}$/', $model->hash);
            $this->assertSame($model->calculateHash(), $model->hash);
        }
    }

    private function makeRecordDto(string $invoiceNumber): RegistrationRecordDto
    {
        return new RegistrationRecordDto(
            invoiceIdentifier: new InvoiceIdentifierDto('12345678Z', $invoiceNumber, new \DateTimeImmutable('2026-08-01')),
            previousInvoiceIdentifier: null,
            previousHash: null,
            isCorrection: false,
            isPriorRejection: false,
            issuerName: 'ACME SL',
            invoiceType: InvoiceType::Simplificada,
            operationDate: null,
            description: 'Chained batch test invoice',
            recipients: [],
            correctiveType: null,
            correctedInvoices: [],
            correctedBaseAmount: null,
            correctedTaxAmount: null,
            replacedInvoices: [],
            breakdownDetails: [
                new BreakdownDetailDto(
                    taxType: TaxType::IVA,
                    regimeType: RegimeType::C01,
                    operationType: OperationType::Subject,
                    baseAmount: '100.00',
                    taxRate: '21.00',
                    taxAmount: '21.00',
                    surchargeRate: null,
                    surchargeAmount: null
                ),
            ],
            totalTaxAmount: '21.00',
            totalAmount: '121.00',
        );
    }
}
