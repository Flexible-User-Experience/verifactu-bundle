<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Handler;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Exception\ValidationException;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Label\Margin\Margin;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use FlexibleUx\VerifactuBundle\Contract\AeatResponseInterface;
use FlexibleUx\VerifactuBundle\Contract\InvoiceIdentifierInterface;
use FlexibleUx\VerifactuBundle\Contract\RegistrationRecordInterface;
use FlexibleUx\VerifactuBundle\Dto\AeatResponseDto;
use FlexibleUx\VerifactuBundle\Dto\RegistrationRecordDto;
use FlexibleUx\VerifactuBundle\Factory\AeatResponseFactory;
use FlexibleUx\VerifactuBundle\Factory\InvoiceIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\RegistrationRecordFactory;
use josemmo\Verifactu\Models\Responses\ResponseStatus;
use josemmo\Verifactu\Services\QrGenerator;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Zxing\QrReader;

final readonly class QrCodeHandler
{
    public const QR_CODE_TRIBUTARY_LEGAL_LABEL = 'QR tributario:'; // this is a mandatory, case-sensitive, legal text label
    public const QR_CODE_VERI_FACTU_LEGAL_LABEL = 'VERI*FACTU'; // this is a mandatory, case-sensitive, legal text label, only allowed when the SIF operates in Veri*Factu mode
    // the khanamiryan decoder cannot read renders bigger than ~500px, so validation runs on a downscaled copy of the generated image
    private const QR_CODE_VALIDATION_SIZE = 300;
    private const TOTAL_AMOUNT_PATTERN = '/^-?\d{1,12}\.\d{2}$/';
    private QrGenerator $qrGenerator;

    public function __construct(
        private array $aeatClientConfig,
        private InvoiceIdentifierFactory $invoiceIdentifierFactory,
        private RegistrationRecordFactory $registrationRecordFactory,
        private AeatResponseFactory $aeatResponseFactory,
    ) {
        $this->qrGenerator = new QrGenerator();
        $this->qrGenerator->setProduction($this->aeatClientConfig['is_prod_environment']);
        $this->qrGenerator->setOnlineMode($this->aeatClientConfig['is_verifactu_mode']);
    }

    /**
     * Build the AEAT QR code URL of an invoice, the only data a legal QR code carries. It depends exclusively on
     * the invoice identifier & total amount, so it can be built before sending the record to AEAT, when the SIF
     * operates in "No Veri*Factu" mode (no AEAT response exists at all) or when reprinting an already sent invoice.
     *
     * @throws ValidationFailedException if the record data does not fulfill the bundle DTO asserts
     */
    public function buildQrCodeUrlFromRegistrationRecordInterface(RegistrationRecordInterface $registrationRecordInterface): string
    {
        return $this->buildQrCodeUrlFromRegistrationRecordDto($this->registrationRecordFactory->makeValidatedRegistrationRecordDtoFromInterface($registrationRecordInterface));
    }

    public function buildQrCodeUrlFromRegistrationRecordDto(RegistrationRecordDto $registrationRecordDto): string
    {
        return $this->buildQrCodeUrl(
            $registrationRecordDto->getInvoiceIdentifier()->getIssuerId(),
            $registrationRecordDto->getInvoiceIdentifier()->getInvoiceNumber(),
            $registrationRecordDto->getInvoiceIdentifier()->getIssueDate(),
            $registrationRecordDto->getTotalAmount()
        );
    }

    /**
     * Build the AEAT QR code URL out of the invoice identifier & total amount alone, the lightest way to rebuild
     * the QR code of an invoice from persisted data without holding a whole registration record.
     *
     * @throws ValidationFailedException if the invoice identifier data does not fulfill the bundle DTO asserts
     * @throws \InvalidArgumentException if the total amount is not a "-?0.00" formatted decimal
     */
    public function buildQrCodeUrlFromInvoiceIdentifierInterface(InvoiceIdentifierInterface $invoiceIdentifierInterface, string $totalAmount): string
    {
        $invoiceIdentifierDto = $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($invoiceIdentifierInterface);

        return $this->buildQrCodeUrl(
            $invoiceIdentifierDto->getIssuerId(),
            $invoiceIdentifierDto->getInvoiceNumber(),
            $invoiceIdentifierDto->getIssueDate(),
            $totalAmount
        );
    }

    /**
     * @throws \InvalidArgumentException if the total amount is not a "-?0.00" formatted decimal
     */
    public function buildQrCodeUrl(string $issuerId, string $invoiceNumber, \DateTimeInterface $issueDate, string $totalAmount): string
    {
        if (1 !== preg_match(self::TOTAL_AMOUNT_PATTERN, $totalAmount)) {
            throw new \InvalidArgumentException(\sprintf('The QR code total amount "%s" must be a decimal with two mandatory decimal digits, e.g. "121.00".', $totalAmount));
        }

        return $this->qrGenerator->from($issuerId, $invoiceNumber, $issueDate, $totalAmount);
    }

    /**
     * @throws ValidationFailedException if the record data does not fulfill the bundle DTO asserts
     * @throws \RuntimeException
     * @throws ValidationException
     */
    public function buildQrCodeAsPngImageFromRegistrationRecordInterface(RegistrationRecordInterface $registrationRecordInterface): ResultInterface
    {
        return $this->buildQrCodeAsPngImageFromUrl($this->buildQrCodeUrlFromRegistrationRecordInterface($registrationRecordInterface));
    }

    /**
     * @throws \RuntimeException
     * @throws ValidationException
     */
    public function buildQrCodeAsPngImageFromRegistrationRecordDto(RegistrationRecordDto $registrationRecordDto): ResultInterface
    {
        return $this->buildQrCodeAsPngImageFromUrl($this->buildQrCodeUrlFromRegistrationRecordDto($registrationRecordDto));
    }

    /**
     * @throws ValidationFailedException if the invoice identifier data does not fulfill the bundle DTO asserts
     * @throws \InvalidArgumentException if the total amount is not a "-?0.00" formatted decimal
     * @throws \RuntimeException
     * @throws ValidationException
     */
    public function buildQrCodeAsPngImageFromInvoiceIdentifierInterface(InvoiceIdentifierInterface $invoiceIdentifierInterface, string $totalAmount): ResultInterface
    {
        return $this->buildQrCodeAsPngImageFromUrl($this->buildQrCodeUrlFromInvoiceIdentifierInterface($invoiceIdentifierInterface, $totalAmount));
    }

    /**
     * @throws \RuntimeException
     * @throws ValidationException
     */
    public function buildQrCodeAsPngImageFromRegistrationRecordAndAeatResponseInterfaces(RegistrationRecordInterface $registrationRecordInterface, AeatResponseInterface $aeatResponseInterface): ResultInterface
    {
        $registrationRecordDto = $this->registrationRecordFactory->makeValidatedRegistrationRecordDtoFromInterface($registrationRecordInterface);
        $aeatResponseDto = $this->aeatResponseFactory->makeValidatedAeatResponseDtoFromInterface($aeatResponseInterface);

        return $this->buildQrCodeAsPngImageFromRegistrationRecordAndAeatResponseDtos($registrationRecordDto, $aeatResponseDto);
    }

    /**
     * @throws \RuntimeException
     * @throws ValidationException
     */
    public function buildQrCodeAsPngImageFromRegistrationRecordAndAeatResponseDtos(RegistrationRecordDto $registrationRecordDto, AeatResponseDto $aeatResponseDto): ResultInterface
    {
        if (ResponseStatus::Incorrect === $aeatResponseDto->getStatus()) {
            throw new \RuntimeException('AEAT response status can not be incorrect');
        }
        if (is_null($aeatResponseDto->getCsv())) {
            throw new \RuntimeException('AEAT CSV response can not be null');
        }

        return $this->buildQrCodeAsPngImageFromRegistrationRecordDto($registrationRecordDto);
    }

    /**
     * @throws ValidationException
     */
    private function buildQrCodeAsPngImageFromUrl(string $qrCodeUrlData): ResultInterface
    {
        $writer = new PngWriter();
        $qrCode = new QrCode(
            data: $qrCodeUrlData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 850,
            margin: 48,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );
        $label = new Label(
            text: $this->aeatClientConfig['is_verifactu_mode'] ? self::QR_CODE_VERI_FACTU_LEGAL_LABEL : self::QR_CODE_TRIBUTARY_LEGAL_LABEL,
            font: new OpenSans(size: 96),
            alignment: LabelAlignment::Center,
            margin: new Margin(0, 0, 24, 0),
            textColor: new Color(0, 0, 0)
        );
        $result = $writer->write(
            qrCode: $qrCode,
            label: $label,
            options: [
                PngWriter::WRITER_OPTION_COMPRESSION_LEVEL => 0,
            ]
        );
        $this->validateQrCodeResult($result, $qrCodeUrlData);

        return $result;
    }

    /**
     * @throws ValidationException
     */
    private function validateQrCodeResult(ResultInterface $result, string $expectedData): void
    {
        $image = imagecreatefromstring($result->getString());
        if (false === $image) {
            throw new ValidationException('Unable to read the generated QR code PNG image');
        }
        $scaledImage = imagescale($image, self::QR_CODE_VALIDATION_SIZE);
        if (false === $scaledImage) {
            throw new ValidationException('Unable to downscale the generated QR code PNG image');
        }
        ob_start();
        imagepng($scaledImage);
        $scaledBlob = (string) ob_get_clean();
        $readData = (new QrReader($scaledBlob, QrReader::SOURCE_TYPE_BLOB, false))->text();
        if ($expectedData !== $readData) {
            throw new ValidationException(\sprintf('The validation reader read "%s" instead of "%s"', \is_string($readData) ? $readData : '', $expectedData));
        }
    }
}
