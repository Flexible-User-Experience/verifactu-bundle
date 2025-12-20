<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use josemmo\Verifactu\Models\Records\RegistrationRecord;

final readonly class RegistrationRecordFactory
{
    private const DEFAULT_COMPUTER_DATE_FORMAT = 'Y-m-d';
    private const DEFAULT_COMPUTER_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function create(RegistrationRecordInterface $input): RegistrationRecordDto
    {
        return new RegistrationRecordDto(
            invoiceIdentifier: $input->getInvoiceIdentifier(),
            previousInvoiceIdentifier: $input->getPreviousInvoiceIdentifier(),
            previousHash: $input->getPreviousHash(),
            hash: $input->getHash(),
            hashedAt: $input->getHashAt(),
            isCorrection: $input->getIsCorrection(),
            isPriorRejection: $input->getIsPriorRejection(),
            issuerName: $input->getIssuerName(),
            invoiceType: $input->getInvoiceType(),
            operationDate: $input->getOperationDate(),
            description: $input->getDescription(),
            recipients: $input->getRecipients(),
            correctiveType: $input->getCorrectiveType(),
            correctedInvoices: $input->getCorrectiveInvoices(),
            correctedBaseAmount: $input->getCorrectedBaseAmount(),
            correctedTaxAmount: $input->getCorrectedTaxAmount(),
            replacedInvoices: $input->getReplacedInvoices(),
            breakdownDetails: $input->getBreakdownDetails(),
            totalTaxAmount: $input->getTotalTaxAmount(),
            totalAmount: $input->getTotalAmount(),
        );
    }

    public function transformDtoToModel(RegistrationRecordInterface $dto): RegistrationRecord
    {
        $record = new RegistrationRecord();
        $record->invoiceId = $dto->getInvoiceIdentifier();
        $record->previousInvoiceId = $dto->getPreviousInvoiceIdentifier();
        $record->previousHash = $dto->getPreviousHash();
        $record->hash = $dto->getHash();
        $record->hashedAt = \DateTimeImmutable::createFromFormat(self::DEFAULT_COMPUTER_DATETIME_FORMAT, $dto->getHashAt()->format(self::DEFAULT_COMPUTER_DATETIME_FORMAT));
        $record->isCorrection = $dto->getIsCorrection();
        $record->isPriorRejection = $dto->getIsPriorRejection();
        $record->issuerName = $dto->getIssuerName();
        $record->invoiceType = $dto->getInvoiceType();
        $record->operationDate = \DateTimeImmutable::createFromFormat(self::DEFAULT_COMPUTER_DATE_FORMAT, $dto->getOperationDate()?->format(self::DEFAULT_COMPUTER_DATE_FORMAT));
        $record->description = $dto->getDescription();
        $record->recipients = $dto->getRecipients();
        $record->correctiveType = $dto->getCorrectiveType();
        $record->correctedInvoices = $dto->getCorrectiveInvoices();
        $record->correctedBaseAmount = $dto->getCorrectedBaseAmount();
        $record->correctedTaxAmount = $dto->getCorrectedTaxAmount();
        $record->replacedInvoices = $dto->getReplacedInvoices();
        $record->breakdown = $dto->getBreakdownDetails();
        $record->totalTaxAmount = $dto->getTotalTaxAmount();
        $record->totalAmount = $dto->getTotalAmount();

        return $record;
    }
}
