<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Handler;

use Flux\VerifactuBundle\Contract\AeatResponseInterface;
use Flux\VerifactuBundle\Contract\CancellationRecordInterface;
use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\AeatResponseDto;
use Flux\VerifactuBundle\Factory\AeatResponseFactory;
use Flux\VerifactuBundle\Factory\CancellationRecordFactory;
use Flux\VerifactuBundle\Factory\RegistrationRecordFactory;
use josemmo\Verifactu\Services\AeatClient;

final readonly class AeatClientHandler
{
    // AEAT limits every remission to 1000 records
    private const MAX_RECORDS_PER_REMISSION = 1000;

    public function __construct(
        private AeatClient $aeatClient,
        private RegistrationRecordFactory $registrationRecordFactory,
        private CancellationRecordFactory $cancellationRecordFactory,
        private AeatResponseFactory $aeatResponseFactory,
    ) {
    }

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

    public function getJsonArrayFromAeatResponseDto(AeatResponseDto $dto): array
    {
        return $this->aeatResponseFactory->getJsonArrayFromAeatResponseDto($dto);
    }

    public function getJsonStringFromAeatResponseDto(AeatResponseDto $dto): string
    {
        return $this->aeatResponseFactory->getJsonStringFromAeatResponseDto($dto);
    }

    private function assertBatchSize(array $recordInterfaces): void
    {
        if ([] === $recordInterfaces || \count($recordInterfaces) > self::MAX_RECORDS_PER_REMISSION) {
            throw new \InvalidArgumentException(\sprintf('A records batch must contain between 1 and %d records, %d given.', self::MAX_RECORDS_PER_REMISSION, \count($recordInterfaces)));
        }
    }
}
