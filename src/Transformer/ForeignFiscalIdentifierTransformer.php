<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Transformer;

use Flux\VerifactuBundle\Contract\ForeignFiscalIdentifierInterface;
use Flux\VerifactuBundle\Dto\ForeignFiscalIdentifierDto;
use josemmo\Verifactu\Models\Records\ForeignFiscalIdentifier;

final readonly class ForeignFiscalIdentifierTransformer extends BaseTransformer
{
    public function transformInterfaceToDto(ForeignFiscalIdentifierInterface $input): ForeignFiscalIdentifierDto
    {
        return new ForeignFiscalIdentifierDto(
            name: self::tt($input->getName()),
            country: self::tt($input->getCountry(), 2),
            type: $input->getType(),
            value: self::tt($input->getValue(), 20),
        );
    }

    public function transformDtoToModel(ForeignFiscalIdentifierDto $dto): ForeignFiscalIdentifier
    {
        $recipient = new ForeignFiscalIdentifier();
        $recipient->name = $dto->getName();
        $recipient->country = $dto->getCountry();
        $recipient->type = $dto->getType();
        $recipient->value = $dto->getValue();

        return $recipient;
    }
}
