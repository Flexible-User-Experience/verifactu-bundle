<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\FiscalIdentifierInterface;
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

    public function makeValidatedFiscalIdentifierDtoFromInterface(FiscalIdentifierInterface $input): FiscalIdentifierDto
    {
        $fiscalIdentifierDto = $this->fiscalIdentifierTransformer->transformInterfaceToDto($input);
        $this->validator->validate($fiscalIdentifierDto);

        return $fiscalIdentifierDto;
    }

    public function makeValidatedFiscalIdentifierModelFromDto(FiscalIdentifierDto $dto): FiscalIdentifier
    {
        $fiscalIdentifierModel = $this->fiscalIdentifierTransformer->transformDtoToModel($dto);
        $fiscalIdentifierModel->validate();

        return $fiscalIdentifierModel;
    }

    public function makeValidatedFiscalIdentifierModel(): FiscalIdentifier
    {
        return $this->makeValidatedFiscalIdentifierModelFromConfigArray($this->fiscalIdentifierConfig);
    }

    public function makeValidatedFiscalIdentifierModelFromConfigArray(array $fiscalIdentifierConfig): FiscalIdentifier
    {
        $fiscalIdentifierDto = $this->fiscalIdentifierTransformer->transformFiscalIdentifierConfigToDto($fiscalIdentifierConfig);
        $this->validator->validate($fiscalIdentifierDto);
        $fiscalIdentifierModel = $this->fiscalIdentifierTransformer->transformDtoToModel($fiscalIdentifierDto);
        $fiscalIdentifierModel->validate();

        return $fiscalIdentifierModel;
    }
}
