<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Dto;

use Flux\VerifactuBundle\Contract\InvoiceIdentifierInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class InvoiceIdentifierDto implements InvoiceIdentifierInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(exactly: 9)]
        private string $issuerId, // TODO apply a better assert "A00000000" | "00000000A"
        #[Assert\NotBlank]
        #[Assert\Length(max: 60)]
        private string $invoiceNumber,
        #[Assert\NotBlank]
        private \DateTimeInterface $issueDate,
    ) {
    }

    public function getIssuerId(): string
    {
        return $this->issuerId;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function getIssueDate(): \DateTimeInterface
    {
        return $this->issueDate;
    }
}
