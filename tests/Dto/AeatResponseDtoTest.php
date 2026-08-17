<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Tests\Dto;

use FlexibleUx\VerifactuBundle\Dto\AeatResponseDto;
use josemmo\Verifactu\Models\Records\InvoiceIdentifier;
use josemmo\Verifactu\Models\Responses\ItemStatus;
use josemmo\Verifactu\Models\Responses\RecordType;
use josemmo\Verifactu\Models\Responses\ResponseItem;
use josemmo\Verifactu\Models\Responses\ResponseStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AeatResponseDtoTest extends TestCase
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
        $dto = new AeatResponseDto(
            csv: 'CSV123456789',
            submittedAt: new \DateTimeImmutable('now'),
            waitSecond: 60,
            status: ResponseStatus::Correct,
            items: []
        );
        $violations = $this->validator->validate($dto);
        $this->assertCount(0, $violations);
    }

    public function testWaitSecondMustBePositive(): void
    {
        $dto = new AeatResponseDto(
            csv: 'CSV123456789',
            submittedAt: new \DateTimeImmutable('now'),
            waitSecond: 0,
            status: ResponseStatus::Correct,
            items: []
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('waitSecond', $violations[0]->getPropertyPath());
    }

    public function testWaitSecondCannotBeNegative(): void
    {
        $dto = new AeatResponseDto(
            csv: 'CSV123456789',
            submittedAt: new \DateTimeImmutable('now'),
            waitSecond: -1,
            status: ResponseStatus::Correct,
            items: []
        );
        $violations = $this->validator->validate($dto);
        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('waitSecond', $violations[0]->getPropertyPath());
    }

    public function testARegisteredRecordIsAccepted(): void
    {
        $dto = $this->makeDto(ResponseStatus::Correct, [$this->makeItem(ItemStatus::Correct)]);

        $this->assertTrue($dto->isAccepted());
        $this->assertCount(1, $dto->getRegisteredItems());
        $this->assertCount(0, $dto->getRejectedItems());
        $this->assertNull($dto->getErrorDescription());
    }

    public function testARecordAcceptedWithErrorsIsStillRegistered(): void
    {
        // "AceptadoConErrores" is stored by AEAT and enters the chain, errors notwithstanding
        $dto = $this->makeDto(ResponseStatus::PartiallyCorrect, [$this->makeItem(ItemStatus::AcceptedWithErrors, '1101', 'Aviso')]);

        $this->assertTrue($dto->isAccepted());
        $this->assertCount(1, $dto->getRegisteredItems());
        $this->assertCount(0, $dto->getRejectedItems());
        $this->assertNull($dto->getErrorDescription());
    }

    public function testARefusedRecordIsNotAccepted(): void
    {
        $dto = $this->makeDto(ResponseStatus::Incorrect, [$this->makeItem(ItemStatus::Incorrect, '3001', 'Error en el registro')]);

        $this->assertFalse($dto->isAccepted());
        $this->assertCount(0, $dto->getRegisteredItems());
        $this->assertCount(1, $dto->getRejectedItems());
        $this->assertSame('Error en el registro', $dto->getErrorDescription());
    }

    public function testAPartiallyCorrectBatchIsNotAcceptedAndTellsBothSidesApart(): void
    {
        $dto = $this->makeDto(ResponseStatus::PartiallyCorrect, [
            $this->makeItem(ItemStatus::Correct),
            $this->makeItem(ItemStatus::Incorrect, '3001', 'Error en el segundo registro'),
            $this->makeItem(ItemStatus::AcceptedWithErrors),
        ]);

        $this->assertFalse($dto->isAccepted());
        $this->assertCount(2, $dto->getRegisteredItems());
        $this->assertCount(1, $dto->getRejectedItems());
        $this->assertSame('Error en el segundo registro', $dto->getErrorDescription());
    }

    public function testAResponseWithoutRecordsIsNeverAccepted(): void
    {
        // an envelope reporting success while carrying no record must not read as an acceptance
        $dto = $this->makeDto(ResponseStatus::Correct, []);

        $this->assertFalse($dto->isAccepted());
        $this->assertNull($dto->getErrorDescription());
    }

    /**
     * @param ResponseItem[] $items
     */
    private function makeDto(ResponseStatus $status, array $items): AeatResponseDto
    {
        return new AeatResponseDto(
            csv: 'CSV123456789',
            submittedAt: new \DateTimeImmutable('now'),
            waitSecond: 60,
            status: $status,
            items: $items
        );
    }

    private function makeItem(ItemStatus $status, ?string $errorCode = null, ?string $errorDescription = null): ResponseItem
    {
        $item = new ResponseItem();
        $item->invoiceId = new InvoiceIdentifier();
        $item->recordType = RecordType::Registration;
        $item->status = $status;
        $item->errorCode = $errorCode;
        $item->errorDescription = $errorDescription;

        return $item;
    }
}
