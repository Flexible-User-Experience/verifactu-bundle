<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use josemmo\Verifactu\Models\Records\RegistrationRecord;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class RegistrationRecordFactory extends BaseFactory
{
    public function create(RegistrationRecordInterface $input): RegistrationRecordInterface
    {
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($input, $violations);
        }

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
        $record->isCorrection = $dto->getIsCorrection();
        $record->isPriorRejection = $dto->getIsPriorRejection();
        $record->issuerName = $dto->getIssuerName();
        $record->invoiceType = $dto->getInvoiceType();
        $record->operationDate = \DateTimeImmutable::createFromFormat('Y-m-d', $dto->getOperationDate()?->format('Y-m-d'));
        $record->description = $dto->getDescription();
        $record->recipients = $dto->getRecipients();
        $record->correctiveType = $dto->getCorrectiveType();
        $record->correctedInvoices = $dto->getCorrectiveInvoices();
        $record->correctedBaseAmount = $dto->getCorrectedBaseAmount();
        $record->correctedTaxAmount = $dto->getCorrectedTaxAmount();

        return $record;
    }
}
