<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Dto;

use Flux\VerifactuBundle\Dto\InvoiceIdentifierDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class InvoiceIdentifierDtoTest extends TestCase
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
        $dto = new InvoiceIdentifierDto(
            issuerId: 'A00000000',
            invoiceNumber: 'INV-2024-0001',
            issueDate: new \DateTimeImmutable('now')
        );
        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testIssuerIdMustHaveExactLength(): void
    {
        $dto = new InvoiceIdentifierDto(
            issuerId: 'A123',
            invoiceNumber: 'INV-1',
            issueDate: new \DateTimeImmutable()
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('issuerId', $violations[0]->getPropertyPath());
    }

    public function testInvoiceNumberCannotBeBlank(): void
    {
        $dto = new InvoiceIdentifierDto(
            issuerId: 'A00000000',
            invoiceNumber: '',
            issueDate: new \DateTimeImmutable()
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('invoiceNumber', $violations[0]->getPropertyPath());
    }
}
