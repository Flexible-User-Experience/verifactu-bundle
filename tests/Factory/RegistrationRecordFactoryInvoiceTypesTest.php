<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Factory;

use Flux\VerifactuBundle\Dto\BreakdownDetailDto;
use Flux\VerifactuBundle\Dto\FiscalIdentifierDto;
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
use josemmo\Verifactu\Models\Records\CorrectiveType;
use josemmo\Verifactu\Models\Records\InvoiceIdentifier;
use josemmo\Verifactu\Models\Records\InvoiceType;
use josemmo\Verifactu\Models\Records\OperationType;
use josemmo\Verifactu\Models\Records\RegimeType;
use josemmo\Verifactu\Models\Records\TaxType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class RegistrationRecordFactoryInvoiceTypesTest extends TestCase
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

    #[DataProvider('provideInvoiceTypes')]
    public function testEveryInvoiceTypeMakesAValidatedModel(
        InvoiceType $invoiceType,
        array $recipients,
        ?CorrectiveType $correctiveType,
        array $correctedInvoices,
        ?string $correctedBaseAmount,
        ?string $correctedTaxAmount,
        array $replacedInvoices,
    ): void {
        $dto = new RegistrationRecordDto(
            invoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-100', new \DateTimeImmutable('2026-08-01')),
            previousInvoiceIdentifier: null,
            previousHash: null,
            isCorrection: false,
            isPriorRejection: false,
            issuerName: 'ACME SL',
            invoiceType: $invoiceType,
            operationDate: null,
            description: sprintf('Invoice type %s test', $invoiceType->value),
            recipients: $recipients,
            correctiveType: $correctiveType,
            correctedInvoices: $correctedInvoices,
            correctedBaseAmount: $correctedBaseAmount,
            correctedTaxAmount: $correctedTaxAmount,
            replacedInvoices: $replacedInvoices,
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
        $model = $this->factory->makeValidatedRegistrationRecordModelFromDto(
            $this->factory->makeValidatedRegistrationRecordDtoFromInterface($dto)
        );
        $this->assertSame($invoiceType, $model->invoiceType);
        $this->assertSame($correctiveType, $model->correctiveType);
        $this->assertCount(\count($recipients), $model->recipients);
        $this->assertCount(\count($correctedInvoices), $model->correctedInvoices);
        $this->assertCount(\count($replacedInvoices), $model->replacedInvoices);
        foreach (array_merge($model->correctedInvoices, $model->replacedInvoices) as $invoiceIdentifierModel) {
            $this->assertInstanceOf(InvoiceIdentifier::class, $invoiceIdentifierModel);
        }
        $this->assertMatchesRegularExpression('/^[0-9A-F]{64}$/', $model->hash);
    }

    public static function provideInvoiceTypes(): iterable
    {
        $recipient = new FiscalIdentifierDto('Client SL', 'B12345678');
        $correctedInvoice = new InvoiceIdentifierDto('12345678Z', 'FA-2026-050', new \DateTimeImmutable('2026-06-01'));
        $replacedInvoice = new InvoiceIdentifierDto('12345678Z', 'TICKET-2026-001', new \DateTimeImmutable('2026-05-01'));

        yield 'F1 complete invoice' => [InvoiceType::Factura, [$recipient], null, [], null, null, []];
        yield 'F2 simplified invoice' => [InvoiceType::Simplificada, [], null, [], null, null, []];
        yield 'F3 substitutive invoice' => [InvoiceType::Sustitutiva, [$recipient], null, [], null, null, [$replacedInvoice]];
        yield 'R1 corrective by differences' => [InvoiceType::R1, [$recipient], CorrectiveType::Differences, [$correctedInvoice], null, null, []];
        yield 'R2 corrective by substitution' => [InvoiceType::R2, [$recipient], CorrectiveType::Substitution, [$correctedInvoice], '100.00', '21.00', []];
        yield 'R3 corrective by differences' => [InvoiceType::R3, [$recipient], CorrectiveType::Differences, [$correctedInvoice], null, null, []];
        yield 'R4 corrective by substitution' => [InvoiceType::R4, [$recipient], CorrectiveType::Substitution, [$correctedInvoice], '100.00', '21.00', []];
        yield 'R5 simplified corrective' => [InvoiceType::R5, [], CorrectiveType::Differences, [$correctedInvoice], null, null, []];
    }
}
