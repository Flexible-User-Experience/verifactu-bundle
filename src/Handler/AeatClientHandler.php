<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

use Flux\VerifactuBundle\Contract\FiscalIdentifierInterface;
use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\FiscalIdentifierDto;
use Flux\VerifactuBundle\Factory\ComputerSystemFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\RegistrationRecordFactory;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Responses\ResponseStatus;
use josemmo\Verifactu\Services\AeatClient;

final readonly class AeatClientHandler
{
    public function __construct(
        private array $aeatClientConfig,
        private array $fiscalIdentifierConfig,
        private RegistrationRecordFactory $registrationRecordFactory,
        private ComputerSystemFactory $computerSystemFactory,
        private FiscalIdentifierFactory $fiscalIdentifierFactory,
        private ContractsValidator $contractsValidator,
    ) {
    }

    public function sendRegistrationRecord(RegistrationRecordInterface $registrationRecord): string
    {
        $validatedRegistrationRecordDto = $this->registrationRecordFactory->makeValidatedRegistrationRecordDtoFromInterface($registrationRecord);
        $validatedFiscalIdentifier = $this->getValidatedFiscalIdentifier();
        $aeatClient = $this->buildAeatClientWithSystemAndTaxpayer($validatedFiscalIdentifier);
        $aeatResponse = $aeatClient->send([
            $this->registrationRecordFactory->makeValidatedRegistrationRecordModelFromDto($validatedRegistrationRecordDto),
        ])->wait();

        return ResponseStatus::Correct === $aeatResponse->status ? 'OK' : 'KO'; // TODO handle response content ('OK' => must return a CSV that needs to be stored somewhere)
    }

    private function getValidatedFiscalIdentifier(): FiscalIdentifierInterface
    {
        $validatedFiscalDto = new FiscalIdentifierDto(
            name: $this->fiscalIdentifierConfig['name'],
            nif: $this->fiscalIdentifierConfig['nif'],
        );
        $validatedFiscalIdentifier = $this->fiscalIdentifierFactory->create($validatedFiscalDto);
        $this->contractsValidator->validate($validatedFiscalIdentifier);

        return $validatedFiscalIdentifier;
    }

    private function buildAeatClientWithSystemAndTaxpayer(FiscalIdentifierInterface $taxpayer): AeatClient
    {
        $client = new AeatClient(
            $this->computerSystemFactory->makeValidatedComputerSystemModel(),
            $this->fiscalIdentifierFactory->transformDtoToModel($taxpayer),
        );
        $client->setCertificate($this->aeatClientConfig['pfx_certificate_filepath'], $this->aeatClientConfig['pfx_certificate_password']);
        $client->setProduction($this->aeatClientConfig['is_prod_environment']);

        return $client;
    }
}
