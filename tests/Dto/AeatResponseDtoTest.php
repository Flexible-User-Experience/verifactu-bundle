<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Dto;

use Flux\VerifactuBundle\Dto\AeatResponseDto;
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
}
