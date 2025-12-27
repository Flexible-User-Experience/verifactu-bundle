<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use Flux\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use Flux\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\RegistrationRecord;

final readonly class RegistrationRecordFactory
{
    public function __construct(
        private InvoiceIdentifierTransformer $invoiceIdentifierTransformer,
        private RegistrationRecordTransformer $registrationRecordTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedRegistrationRecordDtoFromInterface(RegistrationRecordInterface $input): RegistrationRecordDto
    {
        $invoiceIdentifierDto = $this->invoiceIdentifierTransformer->transformInterfaceToDto($input->getInvoiceIdentifier());
        $this->validator->validate($invoiceIdentifierDto);
        if ($input->getPreviousInvoiceIdentifier()) {
            $previousInvoiceIdentifierDto = $this->invoiceIdentifierTransformer->transformInterfaceToDto($input->getPreviousInvoiceIdentifier());
            $this->validator->validate($previousInvoiceIdentifierDto);
        }

        $registrationRecordDto = $this->registrationRecordTransformer->transformInterfaceToDto($input);
        $this->validator->validate($registrationRecordDto);

        return $registrationRecordDto;
    }

    public function makeValidatedRegistrationRecordModelFromDto(RegistrationRecordDto $input): RegistrationRecord
    {
        $invoiceIdentifierDto = $this->invoiceIdentifierTransformer->transformInterfaceToDto($input->getInvoiceIdentifier());
        $previousInvoiceIdentifier = null;
        if ($input->getPreviousInvoiceIdentifier()) {
            $previousInvoiceIdentifierDto = $this->invoiceIdentifierTransformer->transformInterfaceToDto($input->getPreviousInvoiceIdentifier());
            $previousInvoiceIdentifier = $this->invoiceIdentifierTransformer->transformDtoToModel($previousInvoiceIdentifierDto);
        }
        $registrationRecordModel = $this->registrationRecordTransformer->transformDtoToModel(
            dto: $input,
            invoiceIdentifier: $this->invoiceIdentifierTransformer->transformDtoToModel($invoiceIdentifierDto),
            previousInvoiceIdentifier: $previousInvoiceIdentifier,
        );
        $registrationRecordModel->hashedAt = new \DateTimeImmutable();
        $registrationRecordModel->hash = $registrationRecordModel->calculateHash();

        $registrationRecordModel->validate();

        return $registrationRecordModel;
    }
}
