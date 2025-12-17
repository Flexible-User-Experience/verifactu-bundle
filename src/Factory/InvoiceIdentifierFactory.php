<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\InvoiceIdentifierInterface;
use Flux\VerifactuBundle\Dto\InvoiceIdentifierDto;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class InvoiceIdentifierFactory extends BaseFactory
{
    public function create(InvoiceIdentifierInterface $input): InvoiceIdentifierInterface
    {
        $violations = $this->validator->validate($input);
        if (\count($violations) > 0) {
            throw new ValidationFailedException($input, $violations);
        }

        return new InvoiceIdentifierDto(
            issuerId: $this->tt($input->getIssuerId(), 9),
            invoiceNumber: $this->tt($input->getInvoiceNumber(), 60),
            issueDate: $input->getIssueDate(),
        );
    }
}
