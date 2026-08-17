<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Contract;

/**
 * Implemented by the registration & cancellation records a batch remission may chain automatically.
 *
 * Batch sending computes the previous invoice identifier & hash of every record after the first one of the
 * batch, and the record hash is calculated over those values: unless they are written back and persisted, the
 * stored record no longer reproduces the hash the AEAT holds, so it can neither be exported to XML nor remitted
 * again upon an AEAT requirement. That is why AeatClientHandler refuses to chain a record which does not
 * implement this contract instead of silently dropping the chaining it computed.
 */
interface ChainableRecordInterface
{
    public function setPreviousInvoiceIdentifier(InvoiceIdentifierInterface $previousInvoiceIdentifier): self;

    public function setPreviousHash(string $previousHash): self;
}
