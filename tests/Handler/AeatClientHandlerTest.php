<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Tests\Handler;

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

    protected function setUp(): void
    {
        $validator = new ContractsValidator(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator()
        );
        $invoiceIdentifierFactory = new InvoiceIdentifierFactory(new InvoiceIdentifierTransformer(), $validator);
        $this->aeatClient = $this->createMock(AeatClient::class);
        $this->sentRecords = [];
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
        $this->handler = new AeatClientHandler(
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
