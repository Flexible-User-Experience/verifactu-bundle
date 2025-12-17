<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Contract;

use josemmo\Verifactu\Models\Records\CorrectiveType;
use josemmo\Verifactu\Models\Records\InvoiceIdentifier;
use josemmo\Verifactu\Models\Records\InvoiceType;

interface RegistrationRecordInterface
{
    public function getInvoiceIdentifier(): InvoiceIdentifier;

    public function getPreviousInvoiceIdentifier(): ?InvoiceIdentifier;

    public function getPreviousHash(): ?string;

    public function getHash(): string;

    public function getHashAt(): \DateTimeInterface;

    public function getIsCorrection(): bool;

    public function getIsPriorRejection(): ?bool;

    public function getIssuerName(): string;

    public function getInvoiceType(): InvoiceType;

    public function getOperationDate(): ?\DateTimeInterface;

    public function getDescription(): string;

    public function getRecipients(): array;

    public function getCorrectiveType(): ?CorrectiveType;

    public function getCorrectiveInvoices(): array;

    public function getCorrectedBaseAmount(): ?string;

    public function getCorrectedTaxAmount(): ?string;

    public function getReplacedInvoices(): array;

    public function getBreakdownDetails(): array;

    public function getTotalTaxAmount(): string;

    public function getTotalAmount(): string;
}
