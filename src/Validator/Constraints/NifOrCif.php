<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\RegexValidator;

/**
 * Validates the format of a Spanish fiscal identifier: NIF (DNI based "00000000A", NIE or special
 * "X0000000A") or CIF ("A0000000J"), always 9 uppercase characters.
 *
 * Format-only on purpose: control-character checksums are validated by the AEAT itself and a local
 * checksum would reject legitimate special AEAT-assigned identifiers.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::TARGET_PARAMETER)]
final class NifOrCif extends Regex
{
    public const PATTERN = '/^(?:[0-9]{8}[A-Z]|[KLMXYZ][0-9]{7}[A-Z]|[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J])$/';

    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(
            pattern: self::PATTERN,
            message: 'This value is not a valid Spanish NIF or CIF.',
            groups: $groups,
            payload: $payload,
        );
    }

    public function validatedBy(): string
    {
        return RegexValidator::class;
    }
}
