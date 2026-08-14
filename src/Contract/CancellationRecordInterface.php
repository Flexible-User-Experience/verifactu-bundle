<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Contract;

interface CancellationRecordInterface extends ValidatableInterface
{
    public function getInvoiceIdentifier(): InvoiceIdentifierInterface;

    /**
     * Mandatory for every cancellation record to keep the chain ("encadenamiento") integrity.
     */
    public function getPreviousInvoiceIdentifier(): InvoiceIdentifierInterface;

    /**
     * Mandatory for every cancellation record to keep the chain ("encadenamiento") integrity.
     */
    public function getPreviousHash(): string;

    public function getHash(): string;

    public function setHash(string $hash): CancellationRecordInterface;

    public function getHashedAt(): \DateTimeInterface;

    public function setHashedAt(\DateTimeInterface $hashedAt): CancellationRecordInterface;

    /**
     * Set to true when cancelling a record that does not exist in the AEAT or in the SIF ("SinRegistroPrevio").
     */
    public function getWithoutPriorRecord(): bool;

    /**
     * Set to true when resubmitting a cancellation record after it was rejected in its immediately previous remission ("RechazoPrevio").
     */
    public function getIsPriorRejection(): bool;
}
