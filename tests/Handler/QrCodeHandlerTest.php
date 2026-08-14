<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Handler;

use Flux\VerifactuBundle\Dto\AeatResponseDto;
use Flux\VerifactuBundle\Dto\BreakdownDetailDto;
use Flux\VerifactuBundle\Dto\InvoiceIdentifierDto;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use Flux\VerifactuBundle\Factory\AeatResponseFactory;
use Flux\VerifactuBundle\Factory\BreakdownDetailFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\ForeignFiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\InvoiceIdentifierFactory;
use Flux\VerifactuBundle\Factory\RegistrationRecordFactory;
use Flux\VerifactuBundle\Handler\QrCodeHandler;
use Flux\VerifactuBundle\Transformer\AeatResponseTransformer;
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
use josemmo\Verifactu\Models\Responses\ResponseStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;

final class QrCodeHandlerTest extends TestCase
{
    private QrCodeHandler $handler;

    protected function setUp(): void
    {
        $validator = new ContractsValidator(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator()
        );
        $this->handler = new QrCodeHandler(
            ['is_prod_environment' => false],
            new RegistrationRecordFactory(
                new InvoiceIdentifierFactory(new InvoiceIdentifierTransformer(), $validator),
                new BreakdownDetailFactory(new BreakdownDetailTransformer(), $validator),
                new FiscalIdentifierFactory([], new FiscalIdentifierTransformer(), $validator),
                new ForeignFiscalIdentifierFactory(new ForeignFiscalIdentifierTransformer(), $validator),
                new RegistrationRecordTransformer(),
                $validator
            ),
            new AeatResponseFactory(
                new AeatResponseTransformer(
                    new Serializer([new BackedEnumNormalizer(), new DateTimeNormalizer(), new GetSetMethodNormalizer()], [new JsonEncoder()])
                ),
                $validator
            )
        );
    }

    public function testBuildsValidatedQrCodePngImage(): void
    {
        $result = $this->handler->buildQrCodeAsPngImageFromRegistrationRecordAndAeatResponseDtos(
            $this->makeRegistrationRecordDto(),
            $this->makeAeatResponseDto(ResponseStatus::Correct, 'CSV1234567890')
        );
        $this->assertSame('image/png', $result->getMimeType());
        $this->assertStringStartsWith("\x89PNG", $result->getString());
    }

    public function testIncorrectResponseStatusIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AEAT response status can not be incorrect');
        $this->handler->buildQrCodeAsPngImageFromRegistrationRecordAndAeatResponseDtos(
            $this->makeRegistrationRecordDto(),
            $this->makeAeatResponseDto(ResponseStatus::Incorrect, 'CSV1234567890')
        );
    }

    public function testNullCsvIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AEAT CSV response can not be null');
        $this->handler->buildQrCodeAsPngImageFromRegistrationRecordAndAeatResponseDtos(
            $this->makeRegistrationRecordDto(),
            $this->makeAeatResponseDto(ResponseStatus::Correct, null)
        );
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
            description: 'QR code test invoice',
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

    private function makeAeatResponseDto(ResponseStatus $status, ?string $csv): AeatResponseDto
    {
        return new AeatResponseDto(
            csv: $csv,
            submittedAt: new \DateTimeImmutable(),
            waitSecond: 60,
            status: $status,
            items: []
        );
    }
}
