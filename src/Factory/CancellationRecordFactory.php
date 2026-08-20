<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Factory;

use FlexibleUx\VerifactuBundle\Contract\CancellationRecordInterface;
use FlexibleUx\VerifactuBundle\Dto\CancellationRecordDto;
use FlexibleUx\VerifactuBundle\Transformer\CancellationRecordTransformer;
use FlexibleUx\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Exceptions\InvalidModelException;
use josemmo\Verifactu\Models\Records\CancellationRecord;
use josemmo\Verifactu\Models\Records\Record;
use Symfony\Component\Validator\Exception\ValidationFailedException;

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
     * Stamp a cancellation record with its hash & hashedAt values without sending it anywhere, and
     * return the very same instance so the caller can persist them.
     *
     * The annulment counterpart of stampRegistrationRecordFromInterface(): the record of an
     * annulment exists from the moment the annulment is decided, is chained from then on, and only
     * reaches the AEAT afterwards. Remit it with AeatClientHandler::sendStoredCancellationRecord(),
     * which keeps these values instead of stamping fresh ones.
     *
     * @throws ValidationFailedException if the record data does not fulfill the bundle DTO asserts
     * @throws InvalidModelException     if the record data does not fulfill the library model validations
     */
    public function stampCancellationRecordFromInterface(CancellationRecordInterface $input): CancellationRecordInterface
    {
        $cancellationRecordModel = $this->makeValidatedCancellationRecordModelFromDto($this->makeValidatedCancellationRecordDtoFromInterface($input));

        return $input
            ->setHash($cancellationRecordModel->hash)
            ->setHashedAt($cancellationRecordModel->hashedAt)
        ;
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
