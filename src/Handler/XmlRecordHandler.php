<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Handler;

use FlexibleUx\VerifactuBundle\Contract\CancellationRecordInterface;
use FlexibleUx\VerifactuBundle\Contract\RegistrationRecordInterface;
use FlexibleUx\VerifactuBundle\Factory\CancellationRecordFactory;
use FlexibleUx\VerifactuBundle\Factory\ComputerSystemFactory;
use FlexibleUx\VerifactuBundle\Factory\RegistrationRecordFactory;
use josemmo\Verifactu\Exceptions\ImportException;
use josemmo\Verifactu\Exceptions\InvalidModelException;
use josemmo\Verifactu\Models\Records\CancellationRecord;
use josemmo\Verifactu\Models\Records\Record;
use josemmo\Verifactu\Models\Records\RegistrationRecord;
use UXML\UXML;

final readonly class XmlRecordHandler
{
    public function __construct(
        private RegistrationRecordFactory $registrationRecordFactory,
        private CancellationRecordFactory $cancellationRecordFactory,
        private ComputerSystemFactory $computerSystemFactory,
    ) {
    }

    /**
     * Export a previously sent registration record as a standalone "RegistroAlta" XML string, suitable for
     * legal record keeping and re-importable with importRecordFromXmlString(). The record keeps its stored
     * hash & hashedAt values and is re-validated, so any tampering with the persisted data is detected.
     *
     * @throws InvalidModelException if the record data or its stored hash is not valid
     */
    public function exportRegistrationRecordToXmlString(RegistrationRecordInterface $registrationRecordInterface): string
    {
        return $this->exportRecordModelToXmlString($this->registrationRecordFactory->makeValidatedRegistrationRecordModelWithStoredHashFromInterface($registrationRecordInterface));
    }

    /**
     * Export a previously sent cancellation record as a standalone "RegistroAnulacion" XML string, suitable for
     * legal record keeping and re-importable with importRecordFromXmlString(). The record keeps its stored
     * hash & hashedAt values and is re-validated, so any tampering with the persisted data is detected.
     *
     * @throws InvalidModelException if the record data or its stored hash is not valid
     */
    public function exportCancellationRecordToXmlString(CancellationRecordInterface $cancellationRecordInterface): string
    {
        return $this->exportRecordModelToXmlString($this->cancellationRecordFactory->makeValidatedCancellationRecordModelWithStoredHashFromInterface($cancellationRecordInterface));
    }

    /**
     * Import a registration or cancellation record from an XML string: a "RegistroAlta" or "RegistroAnulacion"
     * root element with the "sum1" namespace prefix, as produced by the export methods of this handler.
     *
     * @throws \InvalidArgumentException if the XML string cannot be parsed
     * @throws ImportException           if the XML structure is not a valid record
     * @throws InvalidModelException     if $validate is true and the record data or its hash is not valid
     */
    public function importRecordFromXmlString(string $xml, bool $validate = true): RegistrationRecord|CancellationRecord
    {
        $record = Record::fromXml(UXML::fromString($xml));
        if ($validate) {
            $record->validate();
        }

        return $record;
    }

    private function exportRecordModelToXmlString(Record $record): string
    {
        $wrapperElement = UXML::newInstance('sum1:RegistroFactura', null, [
            'xmlns:sum1' => Record::NS,
        ]);
        $record->export($wrapperElement, $this->computerSystemFactory->makeValidatedComputerSystemModel());
        $recordElement = $wrapperElement->get('sum1:RegistroAlta') ?? $wrapperElement->get('sum1:RegistroAnulacion');
        if (null === $recordElement) {
            throw new \RuntimeException('Unable to find the exported record XML element');
        }

        return $recordElement->asXML();
    }
}
