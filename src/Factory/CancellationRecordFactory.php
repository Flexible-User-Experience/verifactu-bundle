<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\CancellationRecordInterface;
use Flux\VerifactuBundle\Dto\CancellationRecordDto;
use Flux\VerifactuBundle\Transformer\CancellationRecordTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\CancellationRecord;

final readonly class CancellationRecordFactory
{
    public function __construct(
        private InvoiceIdentifierFactory $invoiceIdentifierFactory,
        private CancellationRecordTransformer $cancellationRecordTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedCancellationRecordDtoFromInterface(CancellationRecordInterface $input): CancellationRecordDto
    {
        // validate invoiceIdentifier interface
        $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($input->getInvoiceIdentifier());
        // validate previousInvoiceIdentifier interface (mandatory for cancellation records)
        $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($input->getPreviousInvoiceIdentifier());
        // validate cancellationRecord interface
        $cancellationRecordDto = $this->cancellationRecordTransformer->transformInterfaceToDto($input);
        $this->validator->validate($cancellationRecordDto);

        return $cancellationRecordDto;
    }

    public function makeValidatedCancellationRecordModelFromDto(CancellationRecordDto $input): CancellationRecord
    {
        $invoiceIdentifierDto = $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($input->getInvoiceIdentifier());
        $invoiceIdentifier = $this->invoiceIdentifierFactory->makeValidatedRegistrationRecordModelFromDto($invoiceIdentifierDto);
        $previousInvoiceIdentifierDto = $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($input->getPreviousInvoiceIdentifier());
        $previousInvoiceIdentifier = $this->invoiceIdentifierFactory->makeValidatedRegistrationRecordModelFromDto($previousInvoiceIdentifierDto);
        $cancellationRecordModel = $this->cancellationRecordTransformer->transformDtoToModel(
            dto: $input,
            invoiceIdentifier: $invoiceIdentifier,
            previousInvoiceIdentifier: $previousInvoiceIdentifier,
        );
        $cancellationRecordModel->hashedAt = new \DateTimeImmutable();
        $cancellationRecordModel->hash = $cancellationRecordModel->calculateHash();
        $cancellationRecordModel->validate();

        return $cancellationRecordModel;
    }
}
