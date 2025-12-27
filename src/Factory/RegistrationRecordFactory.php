<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use Flux\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\RegistrationRecord;

final readonly class RegistrationRecordFactory
{
    public function __construct(
        private InvoiceIdentifierFactory $invoiceIdentifierFactory,
        private BreakdownDetailFactory $breakdownDetailFactory,
        private FiscalIdentifierFactory $fiscalIdentifierFactory,
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
            $this->fiscalIdentifierFactory->makeValidatedFiscalIdentifierDtoFromInterface($recipient);
        }
        // validate registrationRecord interface
        $registrationRecordDto = $this->registrationRecordTransformer->transformInterfaceToDto($input);
        $this->validator->validate($registrationRecordDto);

        return $registrationRecordDto;
    }

    public function makeValidatedRegistrationRecordModelFromDto(RegistrationRecordDto $input): RegistrationRecord
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
            $recipientDto = $this->fiscalIdentifierFactory->makeValidatedFiscalIdentifierDtoFromInterface($recipientInterface);
            $recipients[] = $this->fiscalIdentifierFactory->makeValidatedFiscalIdentifierModelFromDto($recipientDto);
        }
        $registrationRecordModel = $this->registrationRecordTransformer->transformDtoToModel(
            dto: $input,
            invoiceIdentifier: $invoiceIdentifier,
            previousInvoiceIdentifier: $previousInvoiceIdentifier,
            breakdownDetails: $breakdownDetails,
            recipients: $recipients,
        );
        $registrationRecordModel->hashedAt = new \DateTimeImmutable();
        $registrationRecordModel->hash = $registrationRecordModel->calculateHash();
        $registrationRecordModel->validate();

        return $registrationRecordModel;
    }
}
