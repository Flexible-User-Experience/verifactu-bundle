<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Dto;

use Flux\VerifactuBundle\Contract\CancellationRecordInterface;
use Flux\VerifactuBundle\Contract\InvoiceIdentifierInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CancellationRecordDto implements CancellationRecordInterface
{
    private string $hash;
    private \DateTimeInterface $hashedAt;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Valid]
        private readonly InvoiceIdentifierInterface $invoiceIdentifier,
        #[Assert\NotBlank]
        #[Assert\Valid]
        private readonly InvoiceIdentifierInterface $previousInvoiceIdentifier,
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[0-9A-F]{64}$/')]
        private readonly string $previousHash,
        #[Assert\NotNull]
        #[Assert\Type('boolean')]
        private readonly bool $withoutPriorRecord,
        #[Assert\NotNull]
        #[Assert\Type('boolean')]
        private readonly bool $isPriorRejection,
    ) {
        $this->hash = '';
        $this->hashedAt = new \DateTimeImmutable();
    }

    public function getInvoiceIdentifier(): InvoiceIdentifierInterface
    {
        return $this->invoiceIdentifier;
    }

    public function getPreviousInvoiceIdentifier(): InvoiceIdentifierInterface
    {
        return $this->previousInvoiceIdentifier;
    }

    public function getPreviousHash(): string
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

    public function getWithoutPriorRecord(): bool
    {
        return $this->withoutPriorRecord;
    }

    public function getIsPriorRejection(): bool
    {
        return $this->isPriorRejection;
    }
}
