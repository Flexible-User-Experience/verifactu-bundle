<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Dto;

use Flux\VerifactuBundle\Dto\CancellationRecordDto;
use Flux\VerifactuBundle\Dto\InvoiceIdentifierDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CancellationRecordDtoTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidDto(): void
    {
        $violations = $this->validator->validate($this->makeDto(strtoupper(hash('sha256', 'previous-record'))));
        $this->assertCount(0, $violations);
    }

    public function testPreviousHashInvalidFormat(): void
    {
        $violations = $this->validator->validate($this->makeDto('not-a-sha256-hash'));
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('previousHash', $violations[0]->getPropertyPath());
    }

    public function testHashAndHashedAtAreInitialized(): void
    {
        $dto = $this->makeDto(strtoupper(hash('sha256', 'previous-record')));
        $this->assertSame('', $dto->getHash());
        $dto->setHash(strtoupper(hash('sha256', 'current-record')));
        $this->assertSame(strtoupper(hash('sha256', 'current-record')), $dto->getHash());
    }

    private function makeDto(string $previousHash): CancellationRecordDto
    {
        return new CancellationRecordDto(
            invoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-002', new \DateTimeImmutable('2026-08-01')),
            previousInvoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-001', new \DateTimeImmutable('2026-07-01')),
            previousHash: $previousHash,
            withoutPriorRecord: false,
            isPriorRejection: false
        );
    }
}
