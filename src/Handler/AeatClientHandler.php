<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Factory\ComputerSystemFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\RegistrationRecordFactory;
use josemmo\Verifactu\Models\Responses\ResponseStatus;
use josemmo\Verifactu\Services\AeatClient;

final readonly class AeatClientHandler
{
    public function __construct(
        private array $aeatClientConfig,
        private RegistrationRecordFactory $registrationRecordFactory,
        private ComputerSystemFactory $computerSystemFactory,
        private FiscalIdentifierFactory $fiscalIdentifierFactory,
    ) {
    }

    public function sendRegistrationRecord(RegistrationRecordInterface $registrationRecord): string
    {
        $validatedRegistrationRecordDto = $this->registrationRecordFactory->makeValidatedRegistrationRecordDtoFromInterface($registrationRecord);
        $aeatClient = $this->buildAeatClientWithSystemAndTaxpayer();
        $aeatResponse = $aeatClient->send([
            $this->registrationRecordFactory->makeValidatedRegistrationRecordModelFromDto($validatedRegistrationRecordDto),
        ])->wait();

        return ResponseStatus::Correct === $aeatResponse->status ? 'OK' : 'KO'; // TODO handle response content ('OK' => must return a CSV that needs to be stored somewhere)
    }

    private function buildAeatClientWithSystemAndTaxpayer(): AeatClient
    {
        $client = new AeatClient(
            $this->computerSystemFactory->makeValidatedComputerSystemModel(),
            $this->fiscalIdentifierFactory->makeValidatedFiscalIdentifierModel(),
        );
        $client->setCertificate($this->aeatClientConfig['pfx_certificate_filepath'], $this->aeatClientConfig['pfx_certificate_password']);
        $client->setProduction($this->aeatClientConfig['is_prod_environment']);

        return $client;
    }
}
