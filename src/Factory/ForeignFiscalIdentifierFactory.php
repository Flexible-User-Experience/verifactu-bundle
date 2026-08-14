<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\ForeignFiscalIdentifierInterface;
use Flux\VerifactuBundle\Dto\ForeignFiscalIdentifierDto;
use Flux\VerifactuBundle\Transformer\ForeignFiscalIdentifierTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\ForeignFiscalIdentifier;

final readonly class ForeignFiscalIdentifierFactory
{
    public function __construct(
        private ForeignFiscalIdentifierTransformer $foreignFiscalIdentifierTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedForeignFiscalIdentifierDtoFromInterface(ForeignFiscalIdentifierInterface $input): ForeignFiscalIdentifierDto
    {
        $foreignFiscalIdentifierDto = $this->foreignFiscalIdentifierTransformer->transformInterfaceToDto($input);
        $this->validator->validate($foreignFiscalIdentifierDto);

        return $foreignFiscalIdentifierDto;
    }

    public function makeValidatedForeignFiscalIdentifierModelFromDto(ForeignFiscalIdentifierDto $dto): ForeignFiscalIdentifier
    {
        $foreignFiscalIdentifierModel = $this->foreignFiscalIdentifierTransformer->transformDtoToModel($dto);
        $foreignFiscalIdentifierModel->validate();

        return $foreignFiscalIdentifierModel;
    }
}
