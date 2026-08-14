<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Factory;

use Flux\VerifactuBundle\Dto\CancellationRecordDto;
use Flux\VerifactuBundle\Dto\InvoiceIdentifierDto;
use Flux\VerifactuBundle\Factory\CancellationRecordFactory;
use Flux\VerifactuBundle\Factory\InvoiceIdentifierFactory;
use Flux\VerifactuBundle\Transformer\CancellationRecordTransformer;
use Flux\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\CancellationRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CancellationRecordFactoryTest extends TestCase
{
    private CancellationRecordFactory $factory;

    protected function setUp(): void
    {
        $validator = new ContractsValidator(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator()
        );
        $this->factory = new CancellationRecordFactory(
            new InvoiceIdentifierFactory(new InvoiceIdentifierTransformer(), $validator),
            new CancellationRecordTransformer(),
            $validator
        );
    }

    public function testMakesValidatedModelWithCalculatedHash(): void
    {
        $previousHash = strtoupper(hash('sha256', 'previous-record'));
        $dto = new CancellationRecordDto(
            invoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-002', new \DateTimeImmutable('2026-08-01')),
            previousInvoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-001', new \DateTimeImmutable('2026-07-01')),
            previousHash: $previousHash,
            withoutPriorRecord: false,
            isPriorRejection: false
        );
        $validatedDto = $this->factory->makeValidatedCancellationRecordDtoFromInterface($dto);
        $model = $this->factory->makeValidatedCancellationRecordModelFromDto($validatedDto);
        $this->assertInstanceOf(CancellationRecord::class, $model);
        $this->assertSame('12345678Z', $model->invoiceId->issuerId);
        $this->assertSame('FA-2026-002', $model->invoiceId->invoiceNumber);
        $this->assertSame('FA-2026-001', $model->previousInvoiceId->invoiceNumber);
        $this->assertSame($previousHash, $model->previousHash);
        $this->assertFalse($model->withoutPriorRecord);
        $this->assertFalse($model->isPriorRejection);
        $this->assertMatchesRegularExpression('/^[0-9A-F]{64}$/', $model->hash);
        $this->assertSame($model->calculateHash(), $model->hash);
    }

    public function testChainedBatchModelsAreLinkedByInvoiceIdentifierAndHash(): void
    {
        $placeholderHash = strtoupper(hash('sha256', 'previous-record'));
        $models = $this->factory->makeValidatedChainedCancellationRecordModelsFromInterfaces([
            $this->makeDto('FA-2026-002', $placeholderHash),
            $this->makeDto('FA-2026-003', $placeholderHash),
        ]);
        $this->assertCount(2, $models);
        $this->assertSame($placeholderHash, $models[0]->previousHash);
        $this->assertSame($models[0]->invoiceId, $models[1]->previousInvoiceId);
        $this->assertSame($models[0]->hash, $models[1]->previousHash);
        $this->assertNotSame($placeholderHash, $models[1]->previousHash);
    }

    private function makeDto(string $invoiceNumber, string $previousHash): CancellationRecordDto
    {
        return new CancellationRecordDto(
            invoiceIdentifier: new InvoiceIdentifierDto('12345678Z', $invoiceNumber, new \DateTimeImmutable('2026-08-01')),
            previousInvoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-001', new \DateTimeImmutable('2026-07-01')),
            previousHash: $previousHash,
            withoutPriorRecord: false,
            isPriorRejection: false
        );
    }
}
