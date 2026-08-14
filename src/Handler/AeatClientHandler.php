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
    // AEAT limits every remission to 1000 records
    private const MAX_RECORDS_PER_REMISSION = 1000;

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

    /**
     * Send a batch of registration records in a single AEAT API call: every record after the first one
     * of the batch is chained to the preceding record automatically.
     *
     * @param RegistrationRecordInterface[] $registrationRecordInterfaces
     */
    public function sendRegistrationRecords(array $registrationRecordInterfaces): AeatResponseInterface
    {
        $this->assertBatchSize($registrationRecordInterfaces);
        $aeatClient = $this->buildAeatClient();
        $validatedRegistrationRecordModels = $this->registrationRecordFactory->makeValidatedChainedRegistrationRecordModelsFromInterfaces($registrationRecordInterfaces);
        $aeatResponse = $aeatClient->send($validatedRegistrationRecordModels)->wait();
        foreach (array_values($registrationRecordInterfaces) as $index => $registrationRecordInterface) {
            $registrationRecordInterface
                ->setHash($validatedRegistrationRecordModels[$index]->hash)
                ->setHashedAt($validatedRegistrationRecordModels[$index]->hashedAt)
            ;
        }

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

    /**
     * Send a batch of cancellation records in a single AEAT API call: every record after the first one
     * of the batch is chained to the preceding record automatically.
     *
     * @param CancellationRecordInterface[] $cancellationRecordInterfaces
     */
    public function sendCancellationRecords(array $cancellationRecordInterfaces): AeatResponseInterface
    {
        $this->assertBatchSize($cancellationRecordInterfaces);
        $aeatClient = $this->buildAeatClient();
        $validatedCancellationRecordModels = $this->cancellationRecordFactory->makeValidatedChainedCancellationRecordModelsFromInterfaces($cancellationRecordInterfaces);
        $aeatResponse = $aeatClient->send($validatedCancellationRecordModels)->wait();
        foreach (array_values($cancellationRecordInterfaces) as $index => $cancellationRecordInterface) {
            $cancellationRecordInterface
                ->setHash($validatedCancellationRecordModels[$index]->hash)
                ->setHashedAt($validatedCancellationRecordModels[$index]->hashedAt)
            ;
        }

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

    private function assertBatchSize(array $recordInterfaces): void
    {
        if ([] === $recordInterfaces || \count($recordInterfaces) > self::MAX_RECORDS_PER_REMISSION) {
            throw new \InvalidArgumentException(\sprintf('A records batch must contain between 1 and %d records, %d given.', self::MAX_RECORDS_PER_REMISSION, \count($recordInterfaces)));
        }
    }
}
