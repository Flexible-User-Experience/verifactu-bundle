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
use FlexibleUx\VerifactuBundle\Contract\RegistrationRecordInterface;
use FlexibleUx\VerifactuBundle\Dto\AeatResponseDto;
use FlexibleUx\VerifactuBundle\Dto\RegistrationRecordDto;
use FlexibleUx\VerifactuBundle\Factory\AeatResponseFactory;
use FlexibleUx\VerifactuBundle\Factory\RegistrationRecordFactory;
use josemmo\Verifactu\Models\Responses\ResponseStatus;
use josemmo\Verifactu\Services\QrGenerator;
use Zxing\QrReader;

final readonly class QrCodeHandler
{
    public const QR_CODE_TOP_LEGAL_LABEL = 'QR tributario:'; // this is a mandatory, case-sensitive, legal text label
    public const QR_CODE_VERI_FACTU_LEGAL_LABEL = 'VERI*FACTU'; // this is a mandatory, case-sensitive, legal text label
    // the khanamiryan decoder cannot read renders bigger than ~500px, so validation runs on a downscaled copy of the generated image
    private const QR_CODE_VALIDATION_SIZE = 300;
    private QrGenerator $qrGenerator;

    public function __construct(
        private array $aeatClientConfig,
        private RegistrationRecordFactory $registrationRecordFactory,
        private AeatResponseFactory $aeatResponseFactory,
    ) {
        $this->qrGenerator = new QrGenerator();
        $this->qrGenerator->setProduction($this->aeatClientConfig['is_prod_environment']);
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
        $qrCodeUrlData = $this->qrGenerator->fromRegistrationRecord($this->registrationRecordFactory->makeValidatedRegistrationRecordModelFromDto($registrationRecordDto));
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
            text: self::QR_CODE_VERI_FACTU_LEGAL_LABEL,
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
