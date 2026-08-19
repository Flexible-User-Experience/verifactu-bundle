<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Tests\Handler;

use FlexibleUx\VerifactuBundle\Dto\AeatResponseDto;
use FlexibleUx\VerifactuBundle\Dto\BreakdownDetailDto;
use FlexibleUx\VerifactuBundle\Dto\InvoiceIdentifierDto;
use FlexibleUx\VerifactuBundle\Dto\RegistrationRecordDto;
use FlexibleUx\VerifactuBundle\Factory\AeatResponseFactory;
use FlexibleUx\VerifactuBundle\Factory\BreakdownDetailFactory;
use FlexibleUx\VerifactuBundle\Factory\FiscalIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\ForeignFiscalIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\InvoiceIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\RegistrationRecordFactory;
use FlexibleUx\VerifactuBundle\Handler\QrCodeHandler;
use FlexibleUx\VerifactuBundle\Transformer\AeatResponseTransformer;
use FlexibleUx\VerifactuBundle\Transformer\BreakdownDetailTransformer;
use FlexibleUx\VerifactuBundle\Transformer\FiscalIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\ForeignFiscalIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use FlexibleUx\VerifactuBundle\Validator\ContractsValidator;
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
        $this->handler = $this->makeHandler();
    }

    private function makeHandler(bool $isVerifactuMode = true): QrCodeHandler
    {
        $validator = new ContractsValidator(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator()
        );

        return new QrCodeHandler(
            ['is_prod_environment' => false, 'is_verifactu_mode' => $isVerifactuMode],
            new InvoiceIdentifierFactory(new InvoiceIdentifierTransformer(), $validator),
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

    public function testBuildsVerifactuQrCodeUrlFromRegistrationRecord(): void
    {
        $this->assertSame(
            'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR?nif=12345678Z&numserie=FA-2026-001&fecha=01-08-2026&importe=121.00',
            $this->handler->buildQrCodeUrlFromRegistrationRecordDto($this->makeRegistrationRecordDto())
        );
    }

    public function testBuildsNoVerifactuQrCodeUrlWhenVerifactuModeIsDisabled(): void
    {
        $this->assertSame(
            'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQRNoVerifactu?nif=12345678Z&numserie=FA-2026-001&fecha=01-08-2026&importe=121.00',
            $this->makeHandler(false)->buildQrCodeUrlFromRegistrationRecordDto($this->makeRegistrationRecordDto())
        );
    }

    public function testBuildsQrCodeUrlFromInvoiceIdentifierAlone(): void
    {
        $this->assertSame(
            'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR?nif=12345678Z&numserie=FA-2026-001&fecha=01-08-2026&importe=121.00',
            $this->handler->buildQrCodeUrlFromInvoiceIdentifierInterface(
                new InvoiceIdentifierDto('12345678Z', 'FA-2026-001', new \DateTimeImmutable('2026-08-01')),
                '121.00'
            )
        );
    }

    public function testMalformedTotalAmountIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The QR code total amount "121,00" must be a decimal');
        $this->handler->buildQrCodeUrl('12345678Z', 'FA-2026-001', new \DateTimeImmutable('2026-08-01'), '121,00');
    }

    public function testBuildsValidatedQrCodePngImageWithoutAnAeatResponse(): void
    {
        $result = $this->handler->buildQrCodeAsPngImageFromInvoiceIdentifierInterface(
            new InvoiceIdentifierDto('12345678Z', 'FA-2026-001', new \DateTimeImmutable('2026-08-01')),
            '121.00'
        );
        $this->assertSame('image/png', $result->getMimeType());
        $this->assertStringStartsWith("\x89PNG", $result->getString());
    }

    public function testBuildsValidatedNoVerifactuQrCodePngImage(): void
    {
        $result = $this->makeHandler(false)->buildQrCodeAsPngImageFromRegistrationRecordDto($this->makeRegistrationRecordDto());
        $this->assertSame('image/png', $result->getMimeType());
        $this->assertStringStartsWith("\x89PNG", $result->getString());
    }

    public function testVerifactuQrCodePngImageCarriesTheLegendBelowTheCode(): void
    {
        [$width, $height] = $this->measurePngImage(
            $this->handler->buildQrCodeAsPngImageFromRegistrationRecordDto($this->makeRegistrationRecordDto())->getString()
        );
        $this->assertGreaterThan($width, $height, 'the VERI*FACTU legend must add a band below the square code');
    }

    public function testNoVerifactuQrCodePngImageCarriesNoLegendAtAll(): void
    {
        [$width, $height] = $this->measurePngImage(
            $this->makeHandler(false)->buildQrCodeAsPngImageFromRegistrationRecordDto($this->makeRegistrationRecordDto())->getString()
        );
        // "QR tributario:" is the only label a non verifiable invoice carries and it always goes
        // above the code, so nothing may be rendered below it: the image stays a bare square.
        $this->assertSame($width, $height);
    }

    /**
     * @return array{int, int} the width and height of a rendered PNG image
     */
    private function measurePngImage(string $pngImage): array
    {
        $image = imagecreatefromstring($pngImage);
        $this->assertNotFalse($image);

        return [imagesx($image), imagesy($image)];
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
