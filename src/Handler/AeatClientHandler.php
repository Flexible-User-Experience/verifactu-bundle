<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Handler;

use FlexibleUx\VerifactuBundle\Contract\AeatResponseInterface;
use FlexibleUx\VerifactuBundle\Contract\CancellationRecordInterface;
use FlexibleUx\VerifactuBundle\Contract\RegistrationRecordInterface;
use FlexibleUx\VerifactuBundle\Dto\AeatResponseDto;
use FlexibleUx\VerifactuBundle\Factory\AeatResponseFactory;
use FlexibleUx\VerifactuBundle\Factory\CancellationRecordFactory;
use FlexibleUx\VerifactuBundle\Factory\RegistrationRecordFactory;
use josemmo\Verifactu\Exceptions\AeatException;
use josemmo\Verifactu\Exceptions\InvalidModelException;
use josemmo\Verifactu\Models\Records\CancellationRecord;
use josemmo\Verifactu\Models\Records\RegistrationRecord;
use josemmo\Verifactu\Services\AeatClient;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class AeatClientHandler
{
    // AEAT limits every remission to 1000 records
    private const MAX_RECORDS_PER_REMISSION = 1000;

    public function __construct(
        private array $aeatClientConfig,
        private AeatClient $aeatClient,
        private RegistrationRecordFactory $registrationRecordFactory,
        private CancellationRecordFactory $cancellationRecordFactory,
        private AeatResponseFactory $aeatResponseFactory,
    ) {
    }

    /**
     * Notify the AEAT of the end of the Veri*Factu voluntary remission ("baja de la remisión voluntaria") right
     * away, with an empty remission carrying the "RemisionVoluntaria" header and no record at all. Pass an end
     * date to override the configured aeat_client.voluntary_remission_end_date one, which is restored on the
     * shared AEAT client afterwards so the following remissions keep their configured header.
     *
     * Note that the returned response carries no record, so isAccepted() is always false for it: read
     * getStatus() instead to tell whether the AEAT accepted the notification.
     *
     * @throws \InvalidArgumentException if no end date is given nor configured, see also sendRegistrationRecord() throws
     * @throws AeatException             if the AEAT server returned a fault or an unparseable response (notification outcome unknown)
     * @throws ClientExceptionInterface  if the request could not be sent (notification outcome unknown)
     */
    public function sendVoluntaryRemissionEndNotification(?\DateTimeInterface $endDate = null, ?bool $isAffectedByIncident = null): AeatResponseInterface
    {
        $endDate ??= $this->makeConfiguredVoluntaryRemissionEndDate();
        if (null === $endDate) {
            throw new \InvalidArgumentException('A voluntary remission end date is mandatory to notify the AEAT: pass it to sendVoluntaryRemissionEndNotification() or set the aeat_client.voluntary_remission_end_date config option.');
        }
        $this->aeatClient->setVoluntaryRemissionEndDate(
            \DateTimeImmutable::createFromInterface($endDate),
            $isAffectedByIncident ?? $this->aeatClientConfig['voluntary_remission_is_affected_by_incident']
        );
        try {
            $aeatResponse = $this->aeatClient->send([])->wait();
        } finally {
            $this->aeatClient->setVoluntaryRemissionEndDate(
                $this->makeConfiguredVoluntaryRemissionEndDate(),
                $this->aeatClientConfig['voluntary_remission_is_affected_by_incident']
            );
        }

        return $this->aeatResponseFactory->makeValidatedAeatResponseDtoFromModel($aeatResponse);
    }

    /**
     * @throws ValidationFailedException if the record data does not fulfill the bundle DTO asserts (nothing is sent)
     * @throws InvalidModelException     if the record data does not fulfill the library model validations (nothing is sent)
     * @throws AeatException             if the AEAT server returned a fault or an unparseable response (remission outcome unknown)
     * @throws ClientExceptionInterface  if the request could not be sent (remission outcome unknown)
     * @throws \InvalidArgumentException if the configured PFX certificate file does not exist or is not readable
     */
    public function sendRegistrationRecord(RegistrationRecordInterface $registrationRecordInterface): AeatResponseInterface
    {
        $validatedRegistrationRecordDto = $this->registrationRecordFactory->makeValidatedRegistrationRecordDtoFromInterface($registrationRecordInterface);
        $validatedRegistrationRecordModel = $this->registrationRecordFactory->makeValidatedRegistrationRecordModelFromDto($validatedRegistrationRecordDto);
        $aeatResponse = $this->aeatClient->send([
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
     *
     * @throws \InvalidArgumentException if the batch does not contain between 1 and 1000 records, see also sendRegistrationRecord() throws
     */
    public function sendRegistrationRecords(array $registrationRecordInterfaces): AeatResponseInterface
    {
        $this->assertBatchSize($registrationRecordInterfaces);
        $validatedRegistrationRecordModels = $this->registrationRecordFactory->makeValidatedChainedRegistrationRecordModelsFromInterfaces($registrationRecordInterfaces);
        $aeatResponse = $this->aeatClient->send($validatedRegistrationRecordModels)->wait();
        foreach (array_values($registrationRecordInterfaces) as $index => $registrationRecordInterface) {
            $registrationRecordInterface
                ->setHash($validatedRegistrationRecordModels[$index]->hash)
                ->setHashedAt($validatedRegistrationRecordModels[$index]->hashedAt)
            ;
        }

        return $this->aeatResponseFactory->makeValidatedAeatResponseDtoFromModel($aeatResponse);
    }

    /**
     * @throws ValidationFailedException if the record data does not fulfill the bundle DTO asserts (nothing is sent)
     * @throws InvalidModelException     if the record data does not fulfill the library model validations (nothing is sent)
     * @throws AeatException             if the AEAT server returned a fault or an unparseable response (remission outcome unknown)
     * @throws ClientExceptionInterface  if the request could not be sent (remission outcome unknown)
     * @throws \InvalidArgumentException if the configured PFX certificate file does not exist or is not readable
     */
    public function sendCancellationRecord(CancellationRecordInterface $cancellationRecordInterface): AeatResponseInterface
    {
        $validatedCancellationRecordDto = $this->cancellationRecordFactory->makeValidatedCancellationRecordDtoFromInterface($cancellationRecordInterface);
        $validatedCancellationRecordModel = $this->cancellationRecordFactory->makeValidatedCancellationRecordModelFromDto($validatedCancellationRecordDto);
        $aeatResponse = $this->aeatClient->send([
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
     *
     * @throws \InvalidArgumentException if the batch does not contain between 1 and 1000 records, see also sendCancellationRecord() throws
     */
    public function sendCancellationRecords(array $cancellationRecordInterfaces): AeatResponseInterface
    {
        $this->assertBatchSize($cancellationRecordInterfaces);
        $validatedCancellationRecordModels = $this->cancellationRecordFactory->makeValidatedChainedCancellationRecordModelsFromInterfaces($cancellationRecordInterfaces);
        $aeatResponse = $this->aeatClient->send($validatedCancellationRecordModels)->wait();
        foreach (array_values($cancellationRecordInterfaces) as $index => $cancellationRecordInterface) {
            $cancellationRecordInterface
                ->setHash($validatedCancellationRecordModels[$index]->hash)
                ->setHashedAt($validatedCancellationRecordModels[$index]->hashedAt)
            ;
        }

        return $this->aeatResponseFactory->makeValidatedAeatResponseDtoFromModel($aeatResponse);
    }

    /**
     * Answer an AEAT requirement ("remisión por requerimiento") with a page of already issued registration
     * records, the way a SIF operating in "No Veri*Factu" mode remits its records when the AEAT asks for them.
     * Set $isLastRequirementSubmission to true on the page that closes the requirement ("FinRequerimiento").
     *
     * The records are sent verbatim, keeping their stored hash & hashedAt values: they are neither re-chained
     * nor re-hashed, because a requirement answer remits the records exactly as they were recorded, so nothing
     * is written back to your entities and any tampering with the persisted data is detected before sending.
     *
     * The requirement reference only applies to this call, the configured aeat_client.requirement_reference
     * one is restored afterwards.
     *
     * @param RegistrationRecordInterface[] $registrationRecordInterfaces
     *
     * @throws \InvalidArgumentException if the requirement reference is blank or the batch does not contain between 1 and 1000 records, see also sendRegistrationRecord() throws
     */
    public function sendRegistrationRecordsUponRequirement(array $registrationRecordInterfaces, string $requirementReference, bool $isLastRequirementSubmission = false): AeatResponseInterface
    {
        $this->assertRequirementReference($requirementReference);
        $this->assertBatchSize($registrationRecordInterfaces);
        $registrationRecordModels = [];
        foreach ($registrationRecordInterfaces as $registrationRecordInterface) {
            $registrationRecordModels[] = $this->registrationRecordFactory->makeValidatedRegistrationRecordModelWithStoredHashFromInterface($registrationRecordInterface);
        }

        return $this->sendRecordModelsUponRequirement($registrationRecordModels, $requirementReference, $isLastRequirementSubmission);
    }

    /**
     * Answer an AEAT requirement ("remisión por requerimiento") with a page of already issued cancellation
     * records, see sendRegistrationRecordsUponRequirement() for the whole behaviour.
     *
     * @param CancellationRecordInterface[] $cancellationRecordInterfaces
     *
     * @throws \InvalidArgumentException if the requirement reference is blank or the batch does not contain between 1 and 1000 records, see also sendCancellationRecord() throws
     */
    public function sendCancellationRecordsUponRequirement(array $cancellationRecordInterfaces, string $requirementReference, bool $isLastRequirementSubmission = false): AeatResponseInterface
    {
        $this->assertRequirementReference($requirementReference);
        $this->assertBatchSize($cancellationRecordInterfaces);
        $cancellationRecordModels = [];
        foreach ($cancellationRecordInterfaces as $cancellationRecordInterface) {
            $cancellationRecordModels[] = $this->cancellationRecordFactory->makeValidatedCancellationRecordModelWithStoredHashFromInterface($cancellationRecordInterface);
        }

        return $this->sendRecordModelsUponRequirement($cancellationRecordModels, $requirementReference, $isLastRequirementSubmission);
    }

    public function getJsonArrayFromAeatResponseDto(AeatResponseDto $dto): array
    {
        return $this->aeatResponseFactory->getJsonArrayFromAeatResponseDto($dto);
    }

    public function getJsonStringFromAeatResponseDto(AeatResponseDto $dto): string
    {
        return $this->aeatResponseFactory->getJsonStringFromAeatResponseDto($dto);
    }

    /**
     * @param array<RegistrationRecord|CancellationRecord> $recordModels
     */
    private function sendRecordModelsUponRequirement(array $recordModels, string $requirementReference, bool $isLastRequirementSubmission): AeatResponseInterface
    {
        $this->aeatClient->setRequirementReference($requirementReference, $isLastRequirementSubmission);
        try {
            $aeatResponse = $this->aeatClient->send($recordModels)->wait();
        } finally {
            $this->aeatClient->setRequirementReference(
                $this->aeatClientConfig['requirement_reference'],
                $this->aeatClientConfig['requirement_is_last_submission']
            );
        }

        return $this->aeatResponseFactory->makeValidatedAeatResponseDtoFromModel($aeatResponse);
    }

    private function makeConfiguredVoluntaryRemissionEndDate(): ?\DateTimeImmutable
    {
        return null !== $this->aeatClientConfig['voluntary_remission_end_date'] ? new \DateTimeImmutable($this->aeatClientConfig['voluntary_remission_end_date']) : null;
    }

    private function assertRequirementReference(string $requirementReference): void
    {
        if ('' === trim($requirementReference)) {
            throw new \InvalidArgumentException('The AEAT requirement reference ("RefRequerimiento") can not be blank.');
        }
    }

    private function assertBatchSize(array $recordInterfaces): void
    {
        if ([] === $recordInterfaces || \count($recordInterfaces) > self::MAX_RECORDS_PER_REMISSION) {
            throw new \InvalidArgumentException(\sprintf('A records batch must contain between 1 and %d records, %d given.', self::MAX_RECORDS_PER_REMISSION, \count($recordInterfaces)));
        }
    }
}
