<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Transformer;

use Flux\VerifactuBundle\Contract\InvoiceIdentifierInterface;
use Flux\VerifactuBundle\Dto\InvoiceIdentifierDto;
use josemmo\Verifactu\Models\Records\InvoiceIdentifier;

final readonly class InvoiceIdentifierTransformer extends BaseTransformer
{
    public function transformInterfaceToDto(InvoiceIdentifierInterface $input): InvoiceIdentifierDto
    {
        return new InvoiceIdentifierDto(
            issuerId: BaseTransformer::tt($input->getIssuerId(), 9),
            invoiceNumber: BaseTransformer::tt($input->getInvoiceNumber(), 60),
            issueDate: $input->getIssueDate(),
        );
    }

    public function transformDtoToModel(InvoiceIdentifierDto $dto): InvoiceIdentifier
    {
        $invoiceIdentifier = new InvoiceIdentifier();
        $invoiceIdentifier->issuerId = $dto->getIssuerId();
        $invoiceIdentifier->invoiceNumber = $dto->getInvoiceNumber();
        $invoiceIdentifier->issueDate = BaseTransformer::makeDateTimeImmutableFromDateTime($dto->getIssueDate());

        return $invoiceIdentifier;
    }
}
