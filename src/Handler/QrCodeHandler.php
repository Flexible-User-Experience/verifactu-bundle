<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Exception\ValidationException;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Flux\VerifactuBundle\Contract\AeatResponseInterface;
use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\AeatResponseDto;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use Flux\VerifactuBundle\Factory\AeatResponseFactory;
use Flux\VerifactuBundle\Factory\RegistrationRecordFactory;
use josemmo\Verifactu\Models\Responses\ResponseStatus;
use josemmo\Verifactu\Services\QrGenerator;

final readonly class QrCodeHandler
{
    private const QR_CODE_VERI_FACTU_LABEL = 'VERI*FACTU'; // this is a mandatory, case-sensitive, legal text label
    private QrGenerator $qrGenerator;

    public function __construct(
        private RegistrationRecordFactory $registrationRecordFactory,
        private AeatResponseFactory $aeatResponseFactory,
    ) {
        $this->qrGenerator = new QrGenerator();
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
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 400,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );
        $label = new Label(
            text: self::QR_CODE_VERI_FACTU_LABEL,
            alignment: LabelAlignment::Center,
            textColor: new Color(0, 0, 0)
        );
        $result = $writer->write(
            qrCode: $qrCode,
            label: $label
        );
        $writer->validateResult($result, $qrCodeUrlData);

        return $result;
    }
}
