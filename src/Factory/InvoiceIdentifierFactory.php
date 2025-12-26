<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\InvoiceIdentifierInterface;
use Flux\VerifactuBundle\Dto\InvoiceIdentifierDto;
use Flux\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\InvoiceIdentifier;

final readonly class InvoiceIdentifierFactory
{
    public function __construct(
        private InvoiceIdentifierTransformer $invoiceIdentifierTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeInvoiceIdentifierDtoFromInterface(InvoiceIdentifierInterface $input): InvoiceIdentifierDto
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
