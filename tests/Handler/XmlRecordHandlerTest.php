<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Handler;

use Flux\VerifactuBundle\Dto\BreakdownDetailDto;
use Flux\VerifactuBundle\Dto\CancellationRecordDto;
use Flux\VerifactuBundle\Dto\InvoiceIdentifierDto;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use Flux\VerifactuBundle\Factory\BreakdownDetailFactory;
use Flux\VerifactuBundle\Factory\CancellationRecordFactory;
use Flux\VerifactuBundle\Factory\ComputerSystemFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\ForeignFiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\InvoiceIdentifierFactory;
use Flux\VerifactuBundle\Factory\RegistrationRecordFactory;
use Flux\VerifactuBundle\Handler\XmlRecordHandler;
use Flux\VerifactuBundle\Transformer\BreakdownDetailTransformer;
use Flux\VerifactuBundle\Transformer\CancellationRecordTransformer;
use Flux\VerifactuBundle\Transformer\ComputerSystemTransformer;
use Flux\VerifactuBundle\Transformer\FiscalIdentifierTransformer;
use Flux\VerifactuBundle\Transformer\ForeignFiscalIdentifierTransformer;
use Flux\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use Flux\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Exceptions\InvalidModelException;
use josemmo\Verifactu\Models\Records\CancellationRecord;
use josemmo\Verifactu\Models\Records\InvoiceType;
use josemmo\Verifactu\Models\Records\OperationType;
use josemmo\Verifactu\Models\Records\RegimeType;
use josemmo\Verifactu\Models\Records\RegistrationRecord;
use josemmo\Verifactu\Models\Records\TaxType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class XmlRecordHandlerTest extends TestCase
{
    private XmlRecordHandler $handler;
    private RegistrationRecordFactory $registrationRecordFactory;
    private CancellationRecordFactory $cancellationRecordFactory;

    protected function setUp(): void
    {
        $validator = new ContractsValidator(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator()
        );
        $invoiceIdentifierFactory = new InvoiceIdentifierFactory(new InvoiceIdentifierTransformer(), $validator);
        $this->registrationRecordFactory = new RegistrationRecordFactory(
            $invoiceIdentifierFactory,
            new BreakdownDetailFactory(new BreakdownDetailTransformer(), $validator),
            new FiscalIdentifierFactory([], new FiscalIdentifierTransformer(), $validator),
            new ForeignFiscalIdentifierFactory(new ForeignFiscalIdentifierTransformer(), $validator),
            new RegistrationRecordTransformer(),
            $validator
        );
        $this->cancellationRecordFactory = new CancellationRecordFactory(
            $invoiceIdentifierFactory,
            new CancellationRecordTransformer(),
            $validator
        );
        $this->handler = new XmlRecordHandler(
            $this->registrationRecordFactory,
            $this->cancellationRecordFactory,
            new ComputerSystemFactory(
                [
                    'vendor_name' => 'Vendor Name SL',
                    'vendor_nif' => 'B12345678',
                    'name' => 'My Software SIF',
                    'id' => '01',
                    'version' => '1.0.0',
                    'installation_number' => 'INST-001',
                    'only_supports_verifactu' => true,
                    'supports_multiple_taxpayers' => false,
                    'has_multiple_taxpayers' => false,
                ],
                new ComputerSystemTransformer(),
                $validator
            )
        );
    }

    public function testRegistrationRecordXmlRoundTrip(): void
    {
        $dto = $this->makeRegistrationRecordDto();
        $sentModel = $this->registrationRecordFactory->makeValidatedRegistrationRecordModelFromDto(
            $this->registrationRecordFactory->makeValidatedRegistrationRecordDtoFromInterface($dto)
        );
        $dto->setHash($sentModel->hash)->setHashedAt($sentModel->hashedAt);
        $xml = $this->handler->exportRegistrationRecordToXmlString($dto);
        $this->assertStringContainsString('RegistroAlta', $xml);
        $this->assertStringContainsString($sentModel->hash, $xml);
        $imported = $this->handler->importRecordFromXmlString($xml);
        $this->assertInstanceOf(RegistrationRecord::class, $imported);
        $this->assertSame($sentModel->hash, $imported->hash);
        $this->assertSame('FA-2026-001', $imported->invoiceId->invoiceNumber);
        $this->assertSame('121.00', $imported->totalAmount);
        $this->assertSame(InvoiceType::Simplificada, $imported->invoiceType);
    }

    public function testCancellationRecordXmlRoundTrip(): void
    {
        $dto = new CancellationRecordDto(
            invoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-002', new \DateTimeImmutable('2026-08-01')),
            previousInvoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-001', new \DateTimeImmutable('2026-07-01')),
            previousHash: strtoupper(hash('sha256', 'previous-record')),
            withoutPriorRecord: false,
            isPriorRejection: false
        );
        $sentModel = $this->cancellationRecordFactory->makeValidatedCancellationRecordModelFromDto(
            $this->cancellationRecordFactory->makeValidatedCancellationRecordDtoFromInterface($dto)
        );
        $dto->setHash($sentModel->hash)->setHashedAt($sentModel->hashedAt);
        $xml = $this->handler->exportCancellationRecordToXmlString($dto);
        $this->assertStringContainsString('RegistroAnulacion', $xml);
        $imported = $this->handler->importRecordFromXmlString($xml);
        $this->assertInstanceOf(CancellationRecord::class, $imported);
        $this->assertSame($sentModel->hash, $imported->hash);
        $this->assertSame('FA-2026-002', $imported->invoiceId->invoiceNumber);
    }

    public function testImportDetectsTamperedRecord(): void
    {
        $dto = $this->makeRegistrationRecordDto();
        $sentModel = $this->registrationRecordFactory->makeValidatedRegistrationRecordModelFromDto(
            $this->registrationRecordFactory->makeValidatedRegistrationRecordDtoFromInterface($dto)
        );
        $dto->setHash($sentModel->hash)->setHashedAt($sentModel->hashedAt);
        $tamperedXml = str_replace('121.00', '122.00', $this->handler->exportRegistrationRecordToXmlString($dto));
        $this->assertInstanceOf(RegistrationRecord::class, $this->handler->importRecordFromXmlString($tamperedXml, false));
        $this->expectException(InvalidModelException::class);
        $this->handler->importRecordFromXmlString($tamperedXml);
    }

    private function makeRegistrationRecordDto(): RegistrationRecordDto
    {
        return new RegistrationRecordDto(
            invoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-001', new \DateTimeImmutable('2026-08-01')),
            previousInvoiceIdentifier: null,
            previousHash: null,
            isCorrection: false,
            isPriorRejection: false,
            issuerName: 'ACME SL',
            invoiceType: InvoiceType::Simplificada,
            operationDate: null,
            description: 'XML round trip test invoice',
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
