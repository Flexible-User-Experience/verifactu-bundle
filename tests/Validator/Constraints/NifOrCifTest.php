<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Validator\Constraints;

use Flux\VerifactuBundle\Validator\Constraints\NifOrCif;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class NifOrCifTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    #[DataProvider('provideValidValues')]
    public function testValidValues(string $value): void
    {
        $violations = $this->validator->validate($value, new NifOrCif());
        $this->assertCount(0, $violations);
    }

    #[DataProvider('provideInvalidValues')]
    public function testInvalidValues(string $value): void
    {
        $violations = $this->validator->validate($value, new NifOrCif());
        $this->assertGreaterThan(0, $violations->count());
    }

    public static function provideValidValues(): iterable
    {
        yield 'DNI based NIF' => ['12345678Z'];
        yield 'NIE' => ['X1234567L'];
        yield 'special AEAT-assigned NIF' => ['M1234567X'];
        yield 'CIF with digit control' => ['B12345678'];
        yield 'CIF with letter control' => ['Q2826000H'];
    }

    public static function provideInvalidValues(): iterable
    {
        yield 'nine digits without control letter' => ['123456789'];
        yield 'lowercase' => ['b12345678'];
        yield 'too short' => ['1234567Z'];
        yield 'too long' => ['123456789Z'];
        yield 'invalid CIF letter' => ['I1234567X'];
        yield 'CIF control letter out of range' => ['B1234567K'];
    }
}
