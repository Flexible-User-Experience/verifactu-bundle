<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Transformer;

use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use josemmo\Verifactu\Models\Records\RegistrationRecord;

final readonly class RegistrationRecordTransformer extends BaseTransformer
{
    public function transformInterfaceToDto(RegistrationRecordInterface $input): RegistrationRecordDto
    {
        return new RegistrationRecordDto(
            invoiceIdentifier: $input->getInvoiceIdentifier(), // TODO make interface validation before
            previousInvoiceIdentifier: $input->getPreviousInvoiceIdentifier(), // TODO make interface validation before
            previousHash: $input->getPreviousHash(),
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
            breakdownDetails: $input->getBreakdownDetails(), // TODO make interface validation before
            totalTaxAmount: $input->getTotalTaxAmount(),
            totalAmount: $input->getTotalAmount(),
        );
    }

    public function transformDtoToModel(RegistrationRecordDto $dto): RegistrationRecord
    {
        $record = new RegistrationRecord();
        $record->invoiceId = $dto->getInvoiceIdentifier();
        $record->previousInvoiceId = $dto->getPreviousInvoiceIdentifier();
        $record->previousHash = $dto->getPreviousHash();
        $record->isCorrection = $dto->getIsCorrection();
        $record->isPriorRejection = $dto->getIsPriorRejection();
        $record->issuerName = $dto->getIssuerName();
        $record->invoiceType = $dto->getInvoiceType();
        $record->operationDate = $dto->getOperationDate() ? BaseTransformer::makeDateTimeImmutableFromDate($dto->getOperationDate()) : null;
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
