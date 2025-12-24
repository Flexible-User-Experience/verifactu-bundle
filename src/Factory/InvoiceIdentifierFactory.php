<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\InvoiceIdentifierInterface;
use Flux\VerifactuBundle\Dto\InvoiceIdentifierDto;
use Flux\VerifactuBundle\Transformer\BaseTransformer;

final readonly class InvoiceIdentifierFactory
{
    public function create(InvoiceIdentifierInterface $input): InvoiceIdentifierDto
    {
        return new InvoiceIdentifierDto(
            issuerId: BaseTransformer::tt($input->getIssuerId(), 9),
            invoiceNumber: BaseTransformer::tt($input->getInvoiceNumber(), 60),
            issueDate: $input->getIssueDate(),
        );
    }
}
