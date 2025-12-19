<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

use Flux\VerifactuBundle\Contract\ComputerSystemInterface;
use Flux\VerifactuBundle\Contract\FiscalIdentifierInterface;
use Flux\VerifactuBundle\Dto\ComputerSystemDto;
use Flux\VerifactuBundle\Dto\FiscalIdentifierDto;
use Flux\VerifactuBundle\Factory\ComputerSystemFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use josemmo\Verifactu\Services\AeatClient;

final readonly class AeatClientHandler
{
    public function __construct(
        private array $aeatClientConfig,
        private array $computerSystemConfig,
        private array $fiscalIdentifierConfig,
        private ComputerSystemFactory $computerSystemFactory,
        private FiscalIdentifierFactory $fiscalIdentifierFactory,
    ) {
    }

    public function getTest(): string
    {
        $validatedComputerSystem = $this->getValidatedComputerSystem();
        $validatedFiscalIdentifier = $this->getValidatedFiscalIdentifier();
        $aeatClient = $this->buildAeatClientWithSystemAndTaxpayer($validatedComputerSystem, $validatedFiscalIdentifier);

        return $validatedComputerSystem->getName().' - '.$validatedFiscalIdentifier->getNif();
    }

    private function getValidatedComputerSystem(): ComputerSystemInterface
    {
        $computerSystemDto = new ComputerSystemDto(
            vendorName: $this->computerSystemConfig['vendor_name'],
            vendorNif: $this->computerSystemConfig['vendor_nif'],
            name: $this->computerSystemConfig['name'],
            id: $this->computerSystemConfig['id'],
            version: $this->computerSystemConfig['version'],
            installationNumber: $this->computerSystemConfig['installation_number'],
            onlySupportsVerifactu: $this->computerSystemConfig['only_supports_verifactu'],
            supportsMultipleTaxpayers: $this->computerSystemConfig['supports_multiple_taxpayers'],
            hasMultipleTaxpayers: $this->computerSystemConfig['has_multiple_taxpayers'],
        );

        return $this->computerSystemFactory->create($computerSystemDto);
    }

    private function getValidatedFiscalIdentifier(): FiscalIdentifierInterface
    {
        $validatedFiscalDto = new FiscalIdentifierDto(
            name: $this->fiscalIdentifierConfig['name'],
            nif: $this->fiscalIdentifierConfig['nif'],
        );

        return $this->fiscalIdentifierFactory->create($validatedFiscalDto);
    }

    private function buildAeatClientWithSystemAndTaxpayer(ComputerSystemInterface $system, FiscalIdentifierInterface $taxpayer): AeatClient
    {
        $client = new AeatClient(
            $this->computerSystemFactory->transformDtoToModel($system),
            $this->fiscalIdentifierFactory->transformDtoToModel($taxpayer),
        );
        $client->setCertificate($this->aeatClientConfig['pfx_certificate_filepath'], $this->aeatClientConfig['pfx_certificate_password']);
        $client->setProduction($this->aeatClientConfig['is_prod_environment']);

        return $client;
    }
}
