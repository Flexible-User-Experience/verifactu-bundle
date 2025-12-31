<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Dto;

use Flux\VerifactuBundle\Contract\BreakdownDetailInterface;
use Flux\VerifactuBundle\Contract\InvoiceIdentifierInterface;
use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use josemmo\Verifactu\Models\Records\CorrectiveType;
use josemmo\Verifactu\Models\Records\InvoiceType;
use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationRecordDto implements RegistrationRecordInterface
{
    private string $hash;
    private \DateTimeInterface $hashedAt;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Valid]
        private readonly InvoiceIdentifierInterface $invoiceIdentifier,
        #[Assert\Valid]
        private readonly ?InvoiceIdentifierInterface $previousInvoiceIdentifier,
        #[Assert\Regex(pattern: '/^[0-9A-F]{64}$/')]
        private readonly ?string $previousHash,
        #[Assert\NotNull]
        #[Assert\Type('boolean')]
        private readonly bool $isCorrection,
        #[Assert\Type('boolean')]
        private readonly ?bool $isPriorRejection,
        #[Assert\NotBlank]
        #[Assert\Length(max: 120)]
        private readonly string $issuerName,
        #[Assert\NotBlank]
        private readonly InvoiceType $invoiceType,
        private readonly ?\DateTimeInterface $operationDate,
        #[Assert\NotBlank]
        #[Assert\Length(max: 500)]
        private readonly string $description,
        #[Assert\Valid]
        #[Assert\Count(max: 1000)]
        private readonly array $recipients,
        private readonly ?CorrectiveType $correctiveType,
        private readonly array $correctedInvoices,
        #[Assert\Regex(pattern: '/^-?\d{1,12}\.\d{2}$/')]
        private readonly ?string $correctedBaseAmount,
        #[Assert\Regex(pattern: '/^-?\d{1,12}\.\d{2}$/')]
        private readonly ?string $correctedTaxAmount,
        private readonly array $replacedInvoices,
        #[Assert\Valid]
        #[Assert\Count(min: 1, max: 12)]
        private readonly array $breakdownDetails,
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^-?\d{1,12}\.\d{2}$/')]
        private readonly string $totalTaxAmount,
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^-?\d{1,12}\.\d{2}$/')]
        private readonly string $totalAmount,
    ) {
        $this->hash = '';
        $this->hashedAt = new \DateTimeImmutable();
    }

    public function getInvoiceIdentifier(): InvoiceIdentifierInterface
    {
        return $this->invoiceIdentifier;
    }

    public function getPreviousInvoiceIdentifier(): ?InvoiceIdentifierInterface
    {
        return $this->previousInvoiceIdentifier;
    }

    public function getPreviousHash(): ?string
    {
        return $this->previousHash;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getHashedAt(): \DateTimeInterface
    {
        return $this->hashedAt;
    }

    public function setHashedAt(\DateTimeInterface $hashedAt): self
    {
        $this->hashedAt = $hashedAt;

        return $this;
    }

    public function getIsCorrection(): bool
    {
        return $this->isCorrection;
    }

    public function getIsPriorRejection(): ?bool
    {
        return $this->isPriorRejection;
    }

    public function getIssuerName(): string
    {
        return $this->issuerName;
    }

    public function getInvoiceType(): InvoiceType
    {
        return $this->invoiceType;
    }

    public function getOperationDate(): ?\DateTimeInterface
    {
        return $this->operationDate;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getCorrectiveType(): ?CorrectiveType
    {
        return $this->correctiveType;
    }

    public function getCorrectiveInvoices(): array
    {
        return $this->correctedInvoices;
    }

    public function getCorrectedBaseAmount(): ?string
    {
        return $this->correctedBaseAmount;
    }

    public function getCorrectedTaxAmount(): ?string
    {
        return $this->correctedTaxAmount;
    }

    public function getReplacedInvoices(): array
    {
        return $this->replacedInvoices;
    }

    /**
     * @return BreakdownDetailInterface[]
     */
    public function getBreakdownDetails(): array
    {
        return $this->breakdownDetails;
    }

    public function getTotalTaxAmount(): string
    {
        return $this->totalTaxAmount;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }
}
