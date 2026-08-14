<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\ForeignFiscalIdentifierInterface;
use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use Flux\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\Record;
use josemmo\Verifactu\Models\Records\RegistrationRecord;

final readonly class RegistrationRecordFactory
{
    public function __construct(
        private InvoiceIdentifierFactory $invoiceIdentifierFactory,
        private BreakdownDetailFactory $breakdownDetailFactory,
        private FiscalIdentifierFactory $fiscalIdentifierFactory,
        private ForeignFiscalIdentifierFactory $foreignFiscalIdentifierFactory,
        private RegistrationRecordTransformer $registrationRecordTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedRegistrationRecordDtoFromInterface(RegistrationRecordInterface $input): RegistrationRecordDto
    {
        // validate invoiceIdentifier interface
        $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($input->getInvoiceIdentifier());
        // validate (if exists) previousInvoiceIdentifier interface
        if ($input->getPreviousInvoiceIdentifier()) {
            $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($input->getPreviousInvoiceIdentifier());
        }
        // validate breakdownDetail interface array
        foreach ($input->getBreakdownDetails() as $breakdownDetail) {
            $this->breakdownDetailFactory->makeValidatedBreakdownDetailDtoFromInterface($breakdownDetail);
        }
        // validate recipients interface array
        foreach ($input->getRecipients() as $recipient) {
            if ($recipient instanceof ForeignFiscalIdentifierInterface) {
                $this->foreignFiscalIdentifierFactory->makeValidatedForeignFiscalIdentifierDtoFromInterface($recipient);
            } else {
                $this->fiscalIdentifierFactory->makeValidatedFiscalIdentifierDtoFromInterface($recipient);
            }
        }
        // validate registrationRecord interface
        $registrationRecordDto = $this->registrationRecordTransformer->transformInterfaceToDto($input);
        $this->validator->validate($registrationRecordDto);

        return $registrationRecordDto;
    }

    /**
     * Build validated & chained registration record models: every record after the first one of the batch
     * is chained to the preceding record (previousInvoiceIdentifier & previousHash are computed automatically).
     *
     * @param RegistrationRecordInterface[] $inputs
     *
     * @return RegistrationRecord[]
     */
    public function makeValidatedChainedRegistrationRecordModelsFromInterfaces(array $inputs): array
    {
        $registrationRecordModels = [];
        $previousRegistrationRecordModel = null;
        foreach ($inputs as $input) {
            $registrationRecordDto = $this->makeValidatedRegistrationRecordDtoFromInterface($input);
            $registrationRecordModel = $this->makeValidatedRegistrationRecordModelFromDto($registrationRecordDto, $previousRegistrationRecordModel);
            $registrationRecordModels[] = $registrationRecordModel;
            $previousRegistrationRecordModel = $registrationRecordModel;
        }

        return $registrationRecordModels;
    }

    public function makeValidatedRegistrationRecordModelFromDto(RegistrationRecordDto $input, ?Record $chainedPreviousRecord = null): RegistrationRecord
    {
        $invoiceIdentifierDto = $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($input->getInvoiceIdentifier());
        $invoiceIdentifier = $this->invoiceIdentifierFactory->makeValidatedRegistrationRecordModelFromDto($invoiceIdentifierDto);
        $previousInvoiceIdentifier = null;
        if ($input->getPreviousInvoiceIdentifier()) {
            $previousInvoiceIdentifierDto = $this->invoiceIdentifierFactory->makeValidatedInvoiceIdentifierDtoFromInterface($input->getPreviousInvoiceIdentifier());
            $previousInvoiceIdentifier = $this->invoiceIdentifierFactory->makeValidatedRegistrationRecordModelFromDto($previousInvoiceIdentifierDto);
        }
        $breakdownDetails = [];
        foreach ($input->getBreakdownDetails() as $breakdownDetailInterface) {
            $breakdownDetailDto = $this->breakdownDetailFactory->makeValidatedBreakdownDetailDtoFromInterface($breakdownDetailInterface);
            $breakdownDetails[] = $this->breakdownDetailFactory->makeValidatedBreakdownDetailModelFromDto($breakdownDetailDto);
        }
        $recipients = [];
        foreach ($input->getRecipients() as $recipientInterface) {
            if ($recipientInterface instanceof ForeignFiscalIdentifierInterface) {
                $recipientDto = $this->foreignFiscalIdentifierFactory->makeValidatedForeignFiscalIdentifierDtoFromInterface($recipientInterface);
                $recipients[] = $this->foreignFiscalIdentifierFactory->makeValidatedForeignFiscalIdentifierModelFromDto($recipientDto);
            } else {
                $recipientDto = $this->fiscalIdentifierFactory->makeValidatedFiscalIdentifierDtoFromInterface($recipientInterface);
                $recipients[] = $this->fiscalIdentifierFactory->makeValidatedFiscalIdentifierModelFromDto($recipientDto);
            }
        }
        $registrationRecordModel = $this->registrationRecordTransformer->transformDtoToModel(
            dto: $input,
            invoiceIdentifier: $invoiceIdentifier,
            previousInvoiceIdentifier: $previousInvoiceIdentifier,
            breakdownDetails: $breakdownDetails,
            recipients: $recipients,
        );
        if (null !== $chainedPreviousRecord) {
            $registrationRecordModel->previousInvoiceId = $chainedPreviousRecord->invoiceId;
            $registrationRecordModel->previousHash = $chainedPreviousRecord->hash;
        }
        $registrationRecordModel->hashedAt = new \DateTimeImmutable();
        $registrationRecordModel->hash = $registrationRecordModel->calculateHash();
        $registrationRecordModel->validate();

        return $registrationRecordModel;
    }
}
