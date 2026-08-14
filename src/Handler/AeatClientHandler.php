<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

use Flux\VerifactuBundle\Contract\AeatResponseInterface;
use Flux\VerifactuBundle\Contract\CancellationRecordInterface;
use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\AeatResponseDto;
use Flux\VerifactuBundle\Factory\AeatResponseFactory;
use Flux\VerifactuBundle\Factory\CancellationRecordFactory;
use Flux\VerifactuBundle\Factory\ComputerSystemFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use Flux\VerifactuBundle\Factory\RegistrationRecordFactory;
use josemmo\Verifactu\Services\AeatClient;

final readonly class AeatClientHandler
{
    public function __construct(
        private array $aeatClientConfig,
        private RegistrationRecordFactory $registrationRecordFactory,
        private CancellationRecordFactory $cancellationRecordFactory,
        private ComputerSystemFactory $computerSystemFactory,
        private FiscalIdentifierFactory $fiscalIdentifierFactory,
        private AeatResponseFactory $aeatResponseFactory,
    ) {
    }

    public function sendRegistrationRecord(RegistrationRecordInterface $registrationRecordInterface): AeatResponseInterface
    {
        $aeatClient = $this->buildAeatClient();
        $validatedRegistrationRecordDto = $this->registrationRecordFactory->makeValidatedRegistrationRecordDtoFromInterface($registrationRecordInterface);
        $validatedRegistrationRecordModel = $this->registrationRecordFactory->makeValidatedRegistrationRecordModelFromDto($validatedRegistrationRecordDto);
        $aeatResponse = $aeatClient->send([
            $validatedRegistrationRecordModel,
        ])->wait();
        $registrationRecordInterface
            ->setHash($validatedRegistrationRecordModel->hash)
            ->setHashedAt($validatedRegistrationRecordModel->hashedAt)
        ;

        return $this->aeatResponseFactory->makeValidatedAeatResponseDtoFromModel($aeatResponse);
    }

    public function sendCancellationRecord(CancellationRecordInterface $cancellationRecordInterface): AeatResponseInterface
    {
        $aeatClient = $this->buildAeatClient();
        $validatedCancellationRecordDto = $this->cancellationRecordFactory->makeValidatedCancellationRecordDtoFromInterface($cancellationRecordInterface);
        $validatedCancellationRecordModel = $this->cancellationRecordFactory->makeValidatedCancellationRecordModelFromDto($validatedCancellationRecordDto);
        $aeatResponse = $aeatClient->send([
            $validatedCancellationRecordModel,
        ])->wait();
        $cancellationRecordInterface
            ->setHash($validatedCancellationRecordModel->hash)
            ->setHashedAt($validatedCancellationRecordModel->hashedAt)
        ;

        return $this->aeatResponseFactory->makeValidatedAeatResponseDtoFromModel($aeatResponse);
    }

    public function getJsonArrayFromAeatResponseDto(AeatResponseDto $dto): array
    {
        return $this->aeatResponseFactory->getJsonArrayFromAeatResponseDto($dto);
    }

    public function getJsonStringFromAeatResponseDto(AeatResponseDto $dto): string
    {
        return $this->aeatResponseFactory->getJsonStringFromAeatResponseDto($dto);
    }

    private function buildAeatClient(): AeatClient
    {
        $client = new AeatClient(
            $this->computerSystemFactory->makeValidatedComputerSystemModel(),
            $this->fiscalIdentifierFactory->makeValidatedFiscalIdentifierModel(),
        );
        $client->setCertificate($this->aeatClientConfig['pfx_certificate_filepath'], $this->aeatClientConfig['pfx_certificate_password']);
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
