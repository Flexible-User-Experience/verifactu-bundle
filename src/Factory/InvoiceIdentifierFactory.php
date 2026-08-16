<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Factory;

use FlexibleUx\VerifactuBundle\Contract\InvoiceIdentifierInterface;
use FlexibleUx\VerifactuBundle\Dto\InvoiceIdentifierDto;
use FlexibleUx\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\InvoiceIdentifier;

final readonly class InvoiceIdentifierFactory
{
    public function __construct(
        private InvoiceIdentifierTransformer $invoiceIdentifierTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedInvoiceIdentifierDtoFromInterface(InvoiceIdentifierInterface $input): InvoiceIdentifierDto
    {
        $invoiceIdentifierDto = $this->invoiceIdentifierTransformer->transformInterfaceToDto($input);
        $this->validator->validate($invoiceIdentifierDto);

        return $invoiceIdentifierDto;
    }

    public function makeValidatedRegistrationRecordModelFromDto(InvoiceIdentifierDto $input): InvoiceIdentifier
    {
        return $this->invoiceIdentifierTransformer->transformDtoToModel($input);
    }
}
