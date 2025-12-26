<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Transformer;

abstract readonly class BaseTransformer
{
    protected const DEFAULT_COMPUTER_DATE_FORMAT = 'Y-m-d';
    protected const DEFAULT_COMPUTER_DATETIME_FORMAT = 'Y-m-d H:i:s';

    public static function trimAndTruncate(string $value, int $maxLength = 120): string
    {
        return mb_substr(trim($value), 0, $maxLength);
    }

    public static function tt(string $value, int $maxLength = 120): string
    {
        return self::trimAndTruncate($value, $maxLength);
    }

    public static function makeDateTimeImmutableFromDate(\DateTimeInterface $value): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(self::DEFAULT_COMPUTER_DATE_FORMAT, $value->format(self::DEFAULT_COMPUTER_DATE_FORMAT));
    }

    public static function makeDateTimeImmutableFromDateTime(\DateTimeInterface $value): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(self::DEFAULT_COMPUTER_DATETIME_FORMAT, $value->format(self::DEFAULT_COMPUTER_DATETIME_FORMAT));
    }
}
