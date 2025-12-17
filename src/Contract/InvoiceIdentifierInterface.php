<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Contract;

interface InvoiceIdentifierInterface
{
    public function getIssuerId(): string;

    public function getInvoiceNumber(): string;

    public function getIssueDate(): \DateTimeInterface;
}
