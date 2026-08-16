<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Factory;

use FlexibleUx\VerifactuBundle\Contract\CancellationRecordInterface;
use FlexibleUx\VerifactuBundle\Dto\CancellationRecordDto;
use FlexibleUx\VerifactuBundle\Transformer\CancellationRecordTransformer;
use FlexibleUx\VerifactuBundle\Validator\ContractsValidator;
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
        $cancellationRecordModel = $this->buildCancellationRecordModelFromDto($input);
        if (null !== $chainedPreviousRecord) {
            $cancellationRecordModel->previousInvoiceId = $chainedPreviousRecord->invoiceId;
            $cancellationRecordModel->previousHash = $chainedPreviousRecord->hash;
        }
        $cancellationRecordModel->hashedAt = new \DateTimeImmutable();
        $cancellationRecordModel->hash = $cancellationRecordModel->calculateHash();
        $cancellationRecordModel->validate();

        return $cancellationRecordModel;
    }

    /**
     * Rebuild a previously sent cancellation record keeping its stored hash & hashedAt values: the
     * validation re-calculates the hash, so any tampering with the persisted record data is detected.
     */
    public function makeValidatedCancellationRecordModelWithStoredHashFromInterface(CancellationRecordInterface $input): CancellationRecord
    {
        $cancellationRecordDto = $this->makeValidatedCancellationRecordDtoFromInterface($input);
        $cancellationRecordModel = $this->buildCancellationRecordModelFromDto($cancellationRecordDto);
        $cancellationRecordModel->hashedAt = \DateTimeImmutable::createFromInterface($input->getHashedAt());
        $cancellationRecordModel->hash = $input->getHash();
        $cancellationRecordModel->validate();

        return $cancellationRecordModel;
    }

    private function buildCancellationRecordModelFromDto(CancellationRecordDto $input): CancellationRecord
    {
        $invoiceIdentifierDto = $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($input->getInvoiceIdentifier());
        $invoiceIdentifier = $this->invoiceIdentifierFactory->makeValidatedRegistrationRecordModelFromDto($invoiceIdentifierDto);
        $previousInvoiceIdentifierDto = $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($input->getPreviousInvoiceIdentifier());
        $previousInvoiceIdentifier = $this->invoiceIdentifierFactory->makeValidatedRegistrationRecordModelFromDto($previousInvoiceIdentifierDto);

        return $this->cancellationRecordTransformer->transformDtoToModel(
            dto: $input,
            invoiceIdentifier: $invoiceIdentifier,
            previousInvoiceIdentifier: $previousInvoiceIdentifier,
        );
    }
}
