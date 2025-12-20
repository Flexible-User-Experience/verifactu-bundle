<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\FiscalIdentifierInterface;
use Flux\VerifactuBundle\Dto\FiscalIdentifierDto;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\FiscalIdentifier;

final readonly class FiscalIdentifierFactory
{
    public function create(FiscalIdentifierInterface $input): FiscalIdentifierDto
    {
        return new FiscalIdentifierDto(
            name: ContractsValidator::tt($input->getName()),
            nif: ContractsValidator::tt($input->getNif(), 9),
        );
    }

    public function transformDtoToModel(FiscalIdentifierInterface $dto): FiscalIdentifier
    {
        $taxpayer = new FiscalIdentifier();
        $taxpayer->name = $dto->getName();
        $taxpayer->nif = $dto->getNif();

        return $taxpayer;
    }
}
