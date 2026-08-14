<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Transformer;

use Flux\VerifactuBundle\Contract\CancellationRecordInterface;
use Flux\VerifactuBundle\Dto\CancellationRecordDto;
use josemmo\Verifactu\Models\Records\CancellationRecord;
use josemmo\Verifactu\Models\Records\InvoiceIdentifier;

final readonly class CancellationRecordTransformer extends BaseTransformer
{
    public function transformInterfaceToDto(CancellationRecordInterface $input): CancellationRecordDto
    {
        return new CancellationRecordDto(
            invoiceIdentifier: $input->getInvoiceIdentifier(),
            previousInvoiceIdentifier: $input->getPreviousInvoiceIdentifier(),
            previousHash: $input->getPreviousHash(),
            withoutPriorRecord: $input->getWithoutPriorRecord(),
            isPriorRejection: $input->getIsPriorRejection(),
        );
    }

    public function transformDtoToModel(
        CancellationRecordDto $dto,
        InvoiceIdentifier $invoiceIdentifier,
        InvoiceIdentifier $previousInvoiceIdentifier,
    ): CancellationRecord {
        $record = new CancellationRecord();
        $record->invoiceId = $invoiceIdentifier;
        $record->previousInvoiceId = $previousInvoiceIdentifier;
        $record->previousHash = $dto->getPreviousHash();
        $record->withoutPriorRecord = $dto->getWithoutPriorRecord();
        $record->isPriorRejection = $dto->getIsPriorRejection();

        return $record;
    }
}
