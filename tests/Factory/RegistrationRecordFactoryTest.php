<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Tests\Factory;

use FlexibleUx\VerifactuBundle\Dto\BreakdownDetailDto;
use FlexibleUx\VerifactuBundle\Dto\InvoiceIdentifierDto;
use FlexibleUx\VerifactuBundle\Dto\RegistrationRecordDto;
use FlexibleUx\VerifactuBundle\Factory\BreakdownDetailFactory;
use FlexibleUx\VerifactuBundle\Factory\FiscalIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\ForeignFiscalIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\InvoiceIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\RegistrationRecordFactory;
use FlexibleUx\VerifactuBundle\Transformer\BreakdownDetailTransformer;
use FlexibleUx\VerifactuBundle\Transformer\FiscalIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\ForeignFiscalIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use FlexibleUx\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Exceptions\InvalidModelException;
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

    /**
     * The stamp is what lets a SIF generate the record when the invoice is issued and remit it
     * later: the hash and the timestamp are fixed here, and every later step, the stored XML copy,
     * the chain and the remission itself, has to reproduce these very values.
     */
    public function testStampingARecordFixesItsHashAndTimestampWithoutSendingAnything(): void
    {
        $record = $this->makeRecordDto('FA-2026-001');
        $this->assertSame('', $record->getHash());

        $stamped = $this->factory->stampRegistrationRecordFromInterface($record);

        $this->assertSame($record, $stamped, 'the very same instance is stamped, so the caller can persist it');
        $this->assertMatchesRegularExpression('/^[0-9A-F]{64}$/', $stamped->getHash());
    }

    /**
     * A stamped record must survive the round trip that a deferred remission puts it through, so
     * that what is finally sent to the AEAT is the record the invoice was issued with.
     */
    public function testAStampedRecordReproducesItsOwnHashWhenRebuiltFromTheStoredValues(): void
    {
        $record = $this->factory->stampRegistrationRecordFromInterface($this->makeRecordDto('FA-2026-001'));

        $model = $this->factory->makeValidatedRegistrationRecordModelWithStoredHashFromInterface($record);

        $this->assertSame($record->getHash(), $model->hash);
        $this->assertSame($record->getHashedAt()->format(\DATE_ATOM), $model->hashedAt->format(\DATE_ATOM));
    }

    public function testARecordAlteredAfterBeingStampedNoLongerReproducesItsHash(): void
    {
        $record = $this->factory->stampRegistrationRecordFromInterface($this->makeRecordDto('FA-2026-001'));
        $record->setHash(str_repeat('A1B2C3D4', 8));

        $this->expectException(InvalidModelException::class);
        $this->factory->makeValidatedRegistrationRecordModelWithStoredHashFromInterface($record);
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
