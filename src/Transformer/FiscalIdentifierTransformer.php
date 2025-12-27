<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Transformer;

use Flux\VerifactuBundle\Contract\FiscalIdentifierInterface;
use Flux\VerifactuBundle\Dto\FiscalIdentifierDto;
use josemmo\Verifactu\Models\Records\FiscalIdentifier;

final readonly class FiscalIdentifierTransformer extends BaseTransformer
{
    public function transformFiscalIdentifierConfigToDto(array $input): FiscalIdentifierDto
    {
        return new FiscalIdentifierDto(
            name: self::tt($input['name']),
            nif: self::tt($input['nif'], 9),
        );
    }

    public function transformInterfaceToDto(FiscalIdentifierInterface $input): FiscalIdentifierDto
    {
        return new FiscalIdentifierDto(
            name: self::tt($input->getName()),
            nif: self::tt($input->getNif(), 9),
        );
    }

    public function transformDtoToModel(FiscalIdentifierDto $dto): FiscalIdentifier
    {
        $taxpayer = new FiscalIdentifier();
        $taxpayer->name = $dto->getName();
        $taxpayer->nif = $dto->getNif();

        return $taxpayer;
    }
}
