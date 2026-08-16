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
use josemmo\Verifactu\Services\AeatClient;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

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
