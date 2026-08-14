<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use josemmo\Verifactu\Services\AeatClient;

final readonly class AeatClientFactory
{
    public function __construct(
        private array $aeatClientConfig,
        private ComputerSystemFactory $computerSystemFactory,
        private FiscalIdentifierFactory $fiscalIdentifierFactory,
    ) {
    }

    public function makeConfiguredAeatClient(): AeatClient
    {
        $pfxCertificateFilepath = $this->aeatClientConfig['pfx_certificate_filepath'];
        if (!is_file($pfxCertificateFilepath) || !is_readable($pfxCertificateFilepath)) {
            throw new \InvalidArgumentException(\sprintf('The configured AEAT client PFX certificate file "%s" does not exist or is not readable.', $pfxCertificateFilepath));
        }
        $client = new AeatClient(
            $this->computerSystemFactory->makeValidatedComputerSystemModel(),
            $this->fiscalIdentifierFactory->makeValidatedFiscalIdentifierModel(),
        );
        $client->setCertificate($pfxCertificateFilepath, $this->aeatClientConfig['pfx_certificate_password']);
        $client->setEntitySeal($this->aeatClientConfig['is_entity_seal_certificate']);
        $client->setProduction($this->aeatClientConfig['is_prod_environment']);
        if (null !== ($this->aeatClientConfig['representative'] ?? null)) {
            $client->setRepresentative($this->fiscalIdentifierFactory->makeValidatedFiscalIdentifierModelFromConfigArray($this->aeatClientConfig['representative']));
        }
        if (null !== $this->aeatClientConfig['requirement_reference']) {
            $client->setRequirementReference($this->aeatClientConfig['requirement_reference'], $this->aeatClientConfig['requirement_is_last_submission']);
        }
        if (null !== $this->aeatClientConfig['voluntary_remission_end_date']) {
            $client->setVoluntaryRemissionEndDate(new \DateTimeImmutable($this->aeatClientConfig['voluntary_remission_end_date']), $this->aeatClientConfig['voluntary_remission_is_affected_by_incident']);
        }

        return $client;
    }
}
