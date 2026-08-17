<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Tests\Handler;

use FlexibleUx\VerifactuBundle\Contract\RegistrationRecordInterface;
use FlexibleUx\VerifactuBundle\Dto\AeatResponseDto;
use FlexibleUx\VerifactuBundle\Dto\BreakdownDetailDto;
use FlexibleUx\VerifactuBundle\Dto\CancellationRecordDto;
use FlexibleUx\VerifactuBundle\Dto\InvoiceIdentifierDto;
use FlexibleUx\VerifactuBundle\Dto\RegistrationRecordDto;
use FlexibleUx\VerifactuBundle\Factory\AeatResponseFactory;
use FlexibleUx\VerifactuBundle\Factory\BreakdownDetailFactory;
use FlexibleUx\VerifactuBundle\Factory\CancellationRecordFactory;
use FlexibleUx\VerifactuBundle\Factory\FiscalIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\ForeignFiscalIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\InvoiceIdentifierFactory;
use FlexibleUx\VerifactuBundle\Factory\RegistrationRecordFactory;
use FlexibleUx\VerifactuBundle\Handler\AeatClientHandler;
use FlexibleUx\VerifactuBundle\Transformer\AeatResponseTransformer;
use FlexibleUx\VerifactuBundle\Transformer\BreakdownDetailTransformer;
use FlexibleUx\VerifactuBundle\Transformer\CancellationRecordTransformer;
use FlexibleUx\VerifactuBundle\Transformer\FiscalIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\ForeignFiscalIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\InvoiceIdentifierTransformer;
use FlexibleUx\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use FlexibleUx\VerifactuBundle\Validator\ContractsValidator;
use GuzzleHttp\Promise\FulfilledPromise;
use josemmo\Verifactu\Models\Records\CancellationRecord;
use josemmo\Verifactu\Models\Records\InvoiceType;
use josemmo\Verifactu\Models\Records\OperationType;
use josemmo\Verifactu\Models\Records\RegimeType;
use josemmo\Verifactu\Models\Records\RegistrationRecord;
use josemmo\Verifactu\Models\Records\TaxType;
use josemmo\Verifactu\Models\Responses\AeatResponse;
use josemmo\Verifactu\Models\Responses\ResponseStatus;
use josemmo\Verifactu\Services\AeatClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;

final class AeatClientHandlerTest extends TestCase
{
    private AeatClient&MockObject $aeatClient;
    private AeatClientHandler $handler;

    /**
     * @var array<RegistrationRecord|CancellationRecord>
     */
    private array $sentRecords = [];

    /**
     * @var array<array{0: ?\DateTimeImmutable, 1: bool}>
     */
    private array $voluntaryRemissionEndDateCalls = [];

    /**
     * @var array<array{0: ?string, 1: bool}>
     */
    private array $requirementReferenceCalls = [];

    protected function setUp(): void
    {
        $this->handler = $this->makeHandler();
    }

    private function makeHandler(?string $configuredVoluntaryRemissionEndDate = null, ?string $configuredRequirementReference = null): AeatClientHandler
    {
        $validator = new ContractsValidator(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator()
        );
        $invoiceIdentifierFactory = new InvoiceIdentifierFactory(new InvoiceIdentifierTransformer(), $validator);
        $this->aeatClient = $this->createMock(AeatClient::class);
        $this->sentRecords = [];
        $this->voluntaryRemissionEndDateCalls = [];
        $this->requirementReferenceCalls = [];
        $this->aeatClient->method('setRequirementReference')->willReturnCallback(function (?string $requirementReference, bool $isLastRequirementSubmission): AeatClient {
            $this->requirementReferenceCalls[] = [$requirementReference, $isLastRequirementSubmission];

            return $this->aeatClient;
        });
        $this->aeatClient->method('setVoluntaryRemissionEndDate')->willReturnCallback(function (?\DateTimeImmutable $endDate, bool $isAffectedByIncident): AeatClient {
            $this->voluntaryRemissionEndDateCalls[] = [$endDate, $isAffectedByIncident];

            return $this->aeatClient;
        });
        $this->aeatClient->method('send')->willReturnCallback(function (array $records): FulfilledPromise {
            $this->sentRecords = $records;
            $aeatResponse = new AeatResponse();
            $aeatResponse->csv = 'CSV1234567890';
            $aeatResponse->submittedAt = new \DateTimeImmutable();
            $aeatResponse->waitSeconds = 60;
            $aeatResponse->status = ResponseStatus::Correct;
            $aeatResponse->items = [];

            return new FulfilledPromise($aeatResponse);
        });

        return new AeatClientHandler(
            [
                'requirement_is_last_submission' => false,
                'requirement_reference' => $configuredRequirementReference,
                'voluntary_remission_end_date' => $configuredVoluntaryRemissionEndDate,
                'voluntary_remission_is_affected_by_incident' => false,
            ],
            $this->aeatClient,
            new RegistrationRecordFactory(
                $invoiceIdentifierFactory,
                new BreakdownDetailFactory(new BreakdownDetailTransformer(), $validator),
                new FiscalIdentifierFactory([], new FiscalIdentifierTransformer(), $validator),
                new ForeignFiscalIdentifierFactory(new ForeignFiscalIdentifierTransformer(), $validator),
                new RegistrationRecordTransformer(),
                $validator
            ),
            new CancellationRecordFactory(
                $invoiceIdentifierFactory,
                new CancellationRecordTransformer(),
                $validator
            ),
            new AeatResponseFactory(
                new AeatResponseTransformer(
                    new Serializer([new BackedEnumNormalizer(), new DateTimeNormalizer(), new GetSetMethodNormalizer()], [new JsonEncoder()])
                ),
                $validator
            )
        );
    }

    public function testSendRegistrationRecordWritesBackHashAndReturnsResponseDto(): void
    {
        $dto = $this->makeRegistrationRecordDto('FA-2026-001');
        $response = $this->handler->sendRegistrationRecord($dto);
        $this->assertCount(1, $this->sentRecords);
        $this->assertInstanceOf(RegistrationRecord::class, $this->sentRecords[0]);
        $this->assertSame($this->sentRecords[0]->hash, $dto->getHash());
        $this->assertSame($this->sentRecords[0]->hashedAt, $dto->getHashedAt());
        $this->assertSame('CSV1234567890', $response->getCsv());
        $this->assertSame(ResponseStatus::Correct, $response->getStatus());
    }

    public function testSendRegistrationRecordsChainsAndWritesBackEveryHash(): void
    {
        $first = $this->makeRegistrationRecordDto('FA-2026-001');
        $second = $this->makeRegistrationRecordDto('FA-2026-002');
        $this->handler->sendRegistrationRecords([$first, $second]);
        $this->assertCount(2, $this->sentRecords);
        $this->assertSame($this->sentRecords[0]->invoiceId, $this->sentRecords[1]->previousInvoiceId);
        $this->assertSame($this->sentRecords[0]->hash, $this->sentRecords[1]->previousHash);
        $this->assertSame($this->sentRecords[0]->hash, $first->getHash());
        $this->assertSame($this->sentRecords[1]->hash, $second->getHash());
    }

    public function testSendCancellationRecordWritesBackHashAndReturnsResponseDto(): void
    {
        $dto = $this->makeCancellationRecordDto();
        $response = $this->handler->sendCancellationRecord($dto);
        $this->assertCount(1, $this->sentRecords);
        $this->assertInstanceOf(CancellationRecord::class, $this->sentRecords[0]);
        $this->assertSame($this->sentRecords[0]->hash, $dto->getHash());
        $this->assertSame('CSV1234567890', $response->getCsv());
    }

    public function testEmptyBatchIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->handler->sendRegistrationRecords([]);
    }

    public function testOversizedBatchIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->handler->sendCancellationRecords(array_fill(0, 1001, $this->makeCancellationRecordDto()));
    }

    public function testSendRecordsChainsAcrossRecordTypes(): void
    {
        $first = $this->makeRegistrationRecordDto('FA-2026-001');
        $second = $this->makeCancellationRecordDto();
        $third = $this->makeRegistrationRecordDto('FA-2026-003');
        $this->handler->sendRecords([$first, $second, $third]);
        $this->assertCount(3, $this->sentRecords);
        $this->assertInstanceOf(RegistrationRecord::class, $this->sentRecords[0]);
        $this->assertInstanceOf(CancellationRecord::class, $this->sentRecords[1]);
        $this->assertInstanceOf(RegistrationRecord::class, $this->sentRecords[2]);
        $this->assertSame($this->sentRecords[0]->invoiceId, $this->sentRecords[1]->previousInvoiceId);
        $this->assertSame($this->sentRecords[0]->hash, $this->sentRecords[1]->previousHash);
        $this->assertSame($this->sentRecords[1]->invoiceId, $this->sentRecords[2]->previousInvoiceId);
        $this->assertSame($this->sentRecords[1]->hash, $this->sentRecords[2]->previousHash);
        $this->assertSame($this->sentRecords[0]->hash, $first->getHash());
        $this->assertSame($this->sentRecords[1]->hash, $second->getHash());
        $this->assertSame($this->sentRecords[2]->hash, $third->getHash());
    }

    public function testSendRecordsWritesBackTheComputedChaining(): void
    {
        $first = $this->makeRegistrationRecordDto('FA-2026-001');
        $second = $this->makeCancellationRecordDto();
        $third = $this->makeRegistrationRecordDto('FA-2026-003');
        $this->handler->sendRecords([$first, $second, $third]);
        $this->assertSame($first->getInvoiceIdentifier(), $second->getPreviousInvoiceIdentifier());
        $this->assertSame($first->getHash(), $second->getPreviousHash());
        $this->assertSame($second->getInvoiceIdentifier(), $third->getPreviousInvoiceIdentifier());
        $this->assertSame($second->getHash(), $third->getPreviousHash());
    }

    public function testBatchedRecordsCanBeRebuiltVerbatimAfterwards(): void
    {
        $first = $this->makeRegistrationRecordDto('FA-2026-001');
        $second = $this->makeRegistrationRecordDto('FA-2026-002');
        $this->handler->sendRecords([$first, $second]);
        $storedHashes = [$first->getHash(), $second->getHash()];
        // the persisted data of every batched record must still reproduce the hash the AEAT holds
        $this->handler->sendRecordsUponRequirement([$first, $second], 'REF00001ABDEAF1234', true);
        $this->assertSame($storedHashes, [$this->sentRecords[0]->hash, $this->sentRecords[1]->hash]);
    }

    public function testSendRecordsRejectsANonChainableRecordAfterTheFirstOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The record #2 of a batch is chained to the preceding one');
        $this->handler->sendRecords([
            $this->makeRegistrationRecordDto('FA-2026-001'),
            $this->createMock(RegistrationRecordInterface::class),
        ]);
        $this->assertSame([], $this->sentRecords);
    }

    public function testSendRecordsRejectsAnUnsupportedRecord(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A records batch only accepts');
        $this->handler->sendRecords([$this->makeRegistrationRecordDto('FA-2026-001'), new \stdClass()]);
    }

    public function testRequirementSubmissionSendsMixedRecordsVerbatim(): void
    {
        $registrationRecord = $this->makeRegistrationRecordDto('FA-2026-001');
        $cancellationRecord = $this->makeCancellationRecordDto();
        $this->handler->sendRecords([$registrationRecord, $cancellationRecord]);
        $storedHashes = [$registrationRecord->getHash(), $cancellationRecord->getHash()];
        $this->handler->sendRecordsUponRequirement([$registrationRecord, $cancellationRecord], 'REF00001ABDEAF1234', true);
        $this->assertCount(2, $this->sentRecords);
        $this->assertInstanceOf(RegistrationRecord::class, $this->sentRecords[0]);
        $this->assertInstanceOf(CancellationRecord::class, $this->sentRecords[1]);
        $this->assertSame($storedHashes, [$this->sentRecords[0]->hash, $this->sentRecords[1]->hash]);
        $this->assertSame($storedHashes, [$registrationRecord->getHash(), $cancellationRecord->getHash()]);
    }

    public function testRequirementSubmissionSendsRecordsWithTheirStoredHashAndRestoresTheConfiguredReference(): void
    {
        $dto = $this->makeRegistrationRecordDto('FA-2026-001');
        $this->handler->sendRegistrationRecord($dto);
        $storedHash = $dto->getHash();
        $storedHashedAt = $dto->getHashedAt();
        $this->handler->sendRegistrationRecordsUponRequirement([$dto], 'REF00001ABDEAF1234', true);
        $this->assertCount(1, $this->sentRecords);
        $this->assertSame($storedHash, $this->sentRecords[0]->hash);
        $this->assertSame($storedHash, $dto->getHash());
        $this->assertSame($storedHashedAt, $dto->getHashedAt());
        $this->assertSame([['REF00001ABDEAF1234', true], [null, false]], $this->requirementReferenceCalls);
    }

    public function testRequirementSubmissionRestoresTheConfiguredReference(): void
    {
        $handler = $this->makeHandler(configuredRequirementReference: 'REF00000CONFIGURED');
        $dto = $this->makeCancellationRecordDto();
        $handler->sendCancellationRecord($dto);
        $handler->sendCancellationRecordsUponRequirement([$dto], 'REF00001ABDEAF1234');
        $this->assertInstanceOf(CancellationRecord::class, $this->sentRecords[0]);
        $this->assertSame([['REF00001ABDEAF1234', false], ['REF00000CONFIGURED', false]], $this->requirementReferenceCalls);
    }

    public function testBlankRequirementReferenceIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('can not be blank');
        $this->handler->sendRegistrationRecordsUponRequirement([$this->makeRegistrationRecordDto('FA-2026-001')], '   ');
    }

    public function testEmptyRequirementBatchIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->handler->sendRegistrationRecordsUponRequirement([], 'REF00001ABDEAF1234');
    }

    public function testVoluntaryRemissionEndNotificationSendsAnEmptyRemission(): void
    {
        $response = $this->handler->sendVoluntaryRemissionEndNotification(new \DateTimeImmutable('2026-12-31'), true);
        $this->assertSame([], $this->sentRecords);
        $this->assertSame(ResponseStatus::Correct, $response->getStatus());
        $this->assertCount(2, $this->voluntaryRemissionEndDateCalls);
        $this->assertSame('2026-12-31', $this->voluntaryRemissionEndDateCalls[0][0]?->format('Y-m-d'));
        $this->assertTrue($this->voluntaryRemissionEndDateCalls[0][1]);
    }

    public function testVoluntaryRemissionEndNotificationFallsBackToTheConfiguredEndDateAndRestoresIt(): void
    {
        $handler = $this->makeHandler('2026-12-31');
        $handler->sendVoluntaryRemissionEndNotification();
        $this->assertSame([], $this->sentRecords);
        $this->assertCount(2, $this->voluntaryRemissionEndDateCalls);
        $this->assertSame('2026-12-31', $this->voluntaryRemissionEndDateCalls[0][0]?->format('Y-m-d'));
        $this->assertSame('2026-12-31', $this->voluntaryRemissionEndDateCalls[1][0]?->format('Y-m-d'));
    }

    public function testVoluntaryRemissionEndNotificationRestoresTheConfiguredHeaderAfterAnOverride(): void
    {
        $handler = $this->makeHandler('2026-12-31');
        $handler->sendVoluntaryRemissionEndNotification(new \DateTimeImmutable('2026-11-30'));
        $this->assertSame('2026-11-30', $this->voluntaryRemissionEndDateCalls[0][0]?->format('Y-m-d'));
        $this->assertSame('2026-12-31', $this->voluntaryRemissionEndDateCalls[1][0]?->format('Y-m-d'));
    }

    public function testVoluntaryRemissionEndNotificationWithoutAnyEndDateIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A voluntary remission end date is mandatory');
        $this->handler->sendVoluntaryRemissionEndNotification();
    }

    public function testGetJsonArrayFromAeatResponseDto(): void
    {
        $dto = new AeatResponseDto(
            csv: 'CSV1234567890',
            submittedAt: new \DateTimeImmutable('2026-08-14T10:00:00+02:00'),
            waitSecond: 60,
            status: ResponseStatus::Correct,
            items: []
        );
        $jsonArray = $this->handler->getJsonArrayFromAeatResponseDto($dto);
        $this->assertSame('CSV1234567890', $jsonArray['csv']);
        $this->assertSame(ResponseStatus::Correct->value, $jsonArray['status']);
        $this->assertJson($this->handler->getJsonStringFromAeatResponseDto($dto));
    }

    private function makeRegistrationRecordDto(string $invoiceNumber): RegistrationRecordDto
    {
        return new RegistrationRecordDto(
            invoiceIdentifier: new InvoiceIdentifierDto('12345678Z', $invoiceNumber, new \DateTimeImmutable('2026-08-01')),
            previousInvoiceIdentifier: null,
            previousHash: null,
            isCorrection: false,
            isPriorRejection: false,
            issuerName: 'ACME SL',
            invoiceType: InvoiceType::Simplificada,
            operationDate: null,
            description: 'Handler test invoice',
            recipients: [],
            correctiveType: null,
            correctedInvoices: [],
            correctedBaseAmount: null,
            correctedTaxAmount: null,
            replacedInvoices: [],
            breakdownDetails: [
                new BreakdownDetailDto(
                    taxType: TaxType::IVA,
                    regimeType: RegimeType::C01,
                    operationType: OperationType::Subject,
                    baseAmount: '100.00',
                    taxRate: '21.00',
                    taxAmount: '21.00',
                    surchargeRate: null,
                    surchargeAmount: null
                ),
            ],
            totalTaxAmount: '21.00',
            totalAmount: '121.00',
        );
    }

    private function makeCancellationRecordDto(): CancellationRecordDto
    {
        return new CancellationRecordDto(
            invoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-002', new \DateTimeImmutable('2026-08-01')),
            previousInvoiceIdentifier: new InvoiceIdentifierDto('12345678Z', 'FA-2026-001', new \DateTimeImmutable('2026-07-01')),
            previousHash: strtoupper(hash('sha256', 'previous-record')),
            withoutPriorRecord: false,
            isPriorRejection: false
        );
    }
}
