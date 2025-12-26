<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Dto\FiscalIdentifierDto;
use Flux\VerifactuBundle\Transformer\FiscalIdentifierTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\FiscalIdentifier;

final readonly class FiscalIdentifierFactory
{
    public function __construct(
        private array $fiscalIdentifierConfig,
        private FiscalIdentifierTransformer $fiscalIdentifierTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedFiscalIdentifierModel(): FiscalIdentifier
    {
        $validatedFiscalIdentifierDto = $this->makeValidatedFiscalIdentifierDto();
        $fiscalIdentifierModel = $this->fiscalIdentifierTransformer->transformDtoToModel($validatedFiscalIdentifierDto);
        $fiscalIdentifierModel->validate();

        return $fiscalIdentifierModel;
    }

    private function makeValidatedFiscalIdentifierDto(): FiscalIdentifierDto
    {
        $fiscalIdentifierDto = $this->makeFiscalIdentifierDto();
        $this->validator->validate($fiscalIdentifierDto);

        return $fiscalIdentifierDto;
    }

    private function makeFiscalIdentifierDto(): FiscalIdentifierDto
    {
        return new FiscalIdentifierDto(
            name: $this->fiscalIdentifierConfig['name'],
            nif: $this->fiscalIdentifierConfig['nif'],
        );
    }
}
