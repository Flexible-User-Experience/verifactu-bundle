<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\CancellationRecordInterface;
use Flux\VerifactuBundle\Dto\CancellationRecordDto;
use Flux\VerifactuBundle\Transformer\CancellationRecordTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\CancellationRecord;
use josemmo\Verifactu\Models\Records\Record;

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

    /**
     * Build validated & chained cancellation record models: every record after the first one of the batch
     * is chained to the preceding record (previousInvoiceIdentifier & previousHash are computed automatically,
     * replacing the interface provided values).
     *
     * @param CancellationRecordInterface[] $inputs
     *
     * @return CancellationRecord[]
     */
    public function makeValidatedChainedCancellationRecordModelsFromInterfaces(array $inputs): array
    {
        $cancellationRecordModels = [];
        $previousCancellationRecordModel = null;
        foreach ($inputs as $input) {
            $cancellationRecordDto = $this->makeValidatedCancellationRecordDtoFromInterface($input);
            $cancellationRecordModel = $this->makeValidatedCancellationRecordModelFromDto($cancellationRecordDto, $previousCancellationRecordModel);
            $cancellationRecordModels[] = $cancellationRecordModel;
            $previousCancellationRecordModel = $cancellationRecordModel;
        }

        return $cancellationRecordModels;
    }

    public function makeValidatedCancellationRecordModelFromDto(CancellationRecordDto $input, ?Record $chainedPreviousRecord = null): CancellationRecord
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
        if (null !== $chainedPreviousRecord) {
            $cancellationRecordModel->previousInvoiceId = $chainedPreviousRecord->invoiceId;
            $cancellationRecordModel->previousHash = $chainedPreviousRecord->hash;
        }
        $cancellationRecordModel->hashedAt = new \DateTimeImmutable();
        $cancellationRecordModel->hash = $cancellationRecordModel->calculateHash();
        $cancellationRecordModel->validate();

        return $cancellationRecordModel;
    }
}
