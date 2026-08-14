<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Factory;

use Flux\VerifactuBundle\Factory\AeatClientFactory;
use Flux\VerifactuBundle\Factory\ComputerSystemFactory;
use Flux\VerifactuBundle\Factory\FiscalIdentifierFactory;
use Flux\VerifactuBundle\Transformer\ComputerSystemTransformer;
use Flux\VerifactuBundle\Transformer\FiscalIdentifierTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\FiscalIdentifier;
use josemmo\Verifactu\Services\AeatClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;

final class AeatClientFactoryTest extends TestCase
{
    private string $pfxCertificateFilepath;

    protected function setUp(): void
    {
        $this->pfxCertificateFilepath = (string) tempnam(sys_get_temp_dir(), 'pfx');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->pfxCertificateFilepath)) {
            unlink($this->pfxCertificateFilepath);
        }
    }

    public function testMakesConfiguredClientWithDefaults(): void
    {
        $client = $this->makeFactory($this->makeAeatClientConfig())->makeConfiguredAeatClient();
        $this->assertInstanceOf(AeatClient::class, $client);
        $this->assertFalse($this->readPrivateProperty($client, 'isProduction'));
        $this->assertFalse($this->readPrivateProperty($client, 'isEntitySeal'));
        $this->assertNull($this->readPrivateProperty($client, 'representative'));
        $this->assertNull($this->readPrivateProperty($client, 'requirementReference'));
        $this->assertNull($this->readPrivateProperty($client, 'voluntaryRemissionEndDate'));
    }

    public function testMakesConfiguredClientWithAllOptions(): void
    {
        $config = $this->makeAeatClientConfig();
        $config['is_entity_seal_certificate'] = true;
        $config['representative'] = ['name' => 'Representative Name', 'nif' => '87654321X'];
        $config['requirement_is_last_submission'] = true;
        $config['requirement_reference'] = 'REQ-2026-001';
        $config['voluntary_remission_end_date'] = '2026-12-31';
        $config['voluntary_remission_is_affected_by_incident'] = true;
        $client = $this->makeFactory($config)->makeConfiguredAeatClient();
        $this->assertTrue($this->readPrivateProperty($client, 'isEntitySeal'));
        $representative = $this->readPrivateProperty($client, 'representative');
        $this->assertInstanceOf(FiscalIdentifier::class, $representative);
        $this->assertSame('87654321X', $representative->nif);
        $this->assertSame('REQ-2026-001', $this->readPrivateProperty($client, 'requirementReference'));
        $this->assertTrue($this->readPrivateProperty($client, 'isLastRequirementSubmission'));
        $voluntaryRemissionEndDate = $this->readPrivateProperty($client, 'voluntaryRemissionEndDate');
        $this->assertInstanceOf(\DateTimeImmutable::class, $voluntaryRemissionEndDate);
        $this->assertSame('2026-12-31', $voluntaryRemissionEndDate->format('Y-m-d'));
        $this->assertTrue($this->readPrivateProperty($client, 'isVoluntaryRemissionAffectedByIncident'));
    }

    public function testMissingPfxCertificateFileIsRejected(): void
    {
        $config = $this->makeAeatClientConfig();
        $config['pfx_certificate_filepath'] = '/path/to/missing/certificate.pfx';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist or is not readable');
        $this->makeFactory($config)->makeConfiguredAeatClient();
    }

    public function testInvalidRepresentativeNifIsRejected(): void
    {
        $config = $this->makeAeatClientConfig();
        $config['representative'] = ['name' => 'Representative Name', 'nif' => '123'];
        $this->expectException(ValidationFailedException::class);
        $this->makeFactory($config)->makeConfiguredAeatClient();
    }

    private function makeAeatClientConfig(): array
    {
        return [
            'is_entity_seal_certificate' => false,
            'is_prod_environment' => false,
            'pfx_certificate_filepath' => $this->pfxCertificateFilepath,
            'pfx_certificate_password' => 'secret',
            'requirement_is_last_submission' => false,
            'requirement_reference' => null,
            'voluntary_remission_end_date' => null,
            'voluntary_remission_is_affected_by_incident' => false,
        ];
    }

    private function makeFactory(array $aeatClientConfig): AeatClientFactory
    {
        $validator = new ContractsValidator(
            Validation::createValidatorBuilder()
                ->enableAttributeMapping()
                ->getValidator()
        );

        return new AeatClientFactory(
            $aeatClientConfig,
            new ComputerSystemFactory(
                [
                    'vendor_name' => 'Vendor Name SL',
                    'vendor_nif' => 'B12345678',
                    'name' => 'My Software SIF',
                    'id' => '01',
                    'version' => '1.0.0',
                    'installation_number' => 'INST-001',
                    'only_supports_verifactu' => true,
                    'supports_multiple_taxpayers' => false,
                    'has_multiple_taxpayers' => false,
                ],
                new ComputerSystemTransformer(),
                $validator
            ),
            new FiscalIdentifierFactory(
                [
                    'name' => 'Taxpayer Name',
                    'nif' => '12345678Z',
                ],
                new FiscalIdentifierTransformer(),
                $validator
            )
        );
    }

    private function readPrivateProperty(AeatClient $client, string $propertyName): mixed
    {
        return (new \ReflectionProperty(AeatClient::class, $propertyName))->getValue($client);
    }
}
