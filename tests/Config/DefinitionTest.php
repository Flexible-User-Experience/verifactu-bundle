<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Tests\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Loader\DefinitionFileLoader;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;

final class DefinitionTest extends TestCase
{
    private const MINIMAL_CONFIG = [
        'aeat_client' => [
            'is_prod_environment' => false,
            'pfx_certificate_filepath' => '/path/to/certificate.pfx',
            'pfx_certificate_password' => 'secret',
        ],
        'computer_system' => [
            'vendor_name' => 'Vendor Name',
            'vendor_nif' => '12345678Z',
            'name' => 'My SIF',
            'id' => 'ID',
            'version' => '1.0',
            'installation_number' => '1',
            'only_supports_verifactu' => false,
            'supports_multiple_taxpayers' => false,
            'has_multiple_taxpayers' => false,
        ],
        'fiscal_identifier' => [
            'name' => 'Taxpayer Name',
            'nif' => '12345678Z',
        ],
    ];

    public function testMinimalConfigurationDisablesEntitySealCertificateByDefault(): void
    {
        $processed = $this->processConfiguration(self::MINIMAL_CONFIG);
        $this->assertFalse($processed['aeat_client']['is_entity_seal_certificate']);
    }

    public function testEntitySealCertificateCanBeEnabled(): void
    {
        $config = self::MINIMAL_CONFIG;
        $config['aeat_client']['is_entity_seal_certificate'] = true;
        $processed = $this->processConfiguration($config);
        $this->assertTrue($processed['aeat_client']['is_entity_seal_certificate']);
    }

    public function testMinimalConfigurationEnablesVerifactuModeByDefault(): void
    {
        $processed = $this->processConfiguration(self::MINIMAL_CONFIG);
        $this->assertTrue($processed['aeat_client']['is_verifactu_mode']);
    }

    public function testVerifactuModeCanBeDisabled(): void
    {
        $config = self::MINIMAL_CONFIG;
        $config['aeat_client']['is_verifactu_mode'] = false;
        $processed = $this->processConfiguration($config);
        $this->assertFalse($processed['aeat_client']['is_verifactu_mode']);
    }

    public function testRemissionHeadersAndRepresentativeDefaults(): void
    {
        $processed = $this->processConfiguration(self::MINIMAL_CONFIG);
        $this->assertArrayNotHasKey('representative', $processed['aeat_client']);
        $this->assertFalse($processed['aeat_client']['requirement_is_last_submission']);
        $this->assertNull($processed['aeat_client']['requirement_reference']);
        $this->assertNull($processed['aeat_client']['voluntary_remission_end_date']);
        $this->assertFalse($processed['aeat_client']['voluntary_remission_is_affected_by_incident']);
    }

    public function testRepresentativeCanBeConfigured(): void
    {
        $config = self::MINIMAL_CONFIG;
        $config['aeat_client']['representative'] = [
            'name' => 'Representative Name',
            'nif' => '87654321X',
        ];
        $processed = $this->processConfiguration($config);
        $this->assertSame('Representative Name', $processed['aeat_client']['representative']['name']);
        $this->assertSame('87654321X', $processed['aeat_client']['representative']['nif']);
    }

    public function testVoluntaryRemissionEndDateRejectsInvalidDateFormat(): void
    {
        $config = self::MINIMAL_CONFIG;
        $config['aeat_client']['voluntary_remission_end_date'] = '31-12-2026';
        $this->expectException(InvalidConfigurationException::class);
        $this->processConfiguration($config);
    }

    public function testStatementOfResponsibilityDefaultsToAnEmptyDocumentContent(): void
    {
        $processed = $this->processConfiguration(self::MINIMAL_CONFIG);
        $this->assertSame([], $processed['statement_of_responsibility']['composition']);
        $this->assertSame([], $processed['statement_of_responsibility']['functionalities']);
        $this->assertSame([], $processed['statement_of_responsibility']['installation_characteristics']);
        $this->assertNull($processed['statement_of_responsibility']['typology']);
        $this->assertNull($processed['statement_of_responsibility']['vendor_address']);
    }

    public function testStatementOfResponsibilityCanBeConfigured(): void
    {
        $config = self::MINIMAL_CONFIG;
        $config['statement_of_responsibility'] = [
            'composition' => ['Módulo de facturación', 'Biblioteca josemmo/verifactu-php'],
            'functionalities' => ['Generación del código QR tributario'],
            'installation_characteristics' => ['Instalación SaaS sobre servidor propio en la UE'],
            'typology' => 'Sistema informático de facturación de uso propio',
            'vendor_address' => 'Carrer Major, 1 — 43870 Amposta (Tarragona), España',
        ];
        $processed = $this->processConfiguration($config);
        $this->assertSame(['Módulo de facturación', 'Biblioteca josemmo/verifactu-php'], $processed['statement_of_responsibility']['composition']);
        $this->assertSame(['Generación del código QR tributario'], $processed['statement_of_responsibility']['functionalities']);
        $this->assertSame(['Instalación SaaS sobre servidor propio en la UE'], $processed['statement_of_responsibility']['installation_characteristics']);
        $this->assertSame('Sistema informático de facturación de uso propio', $processed['statement_of_responsibility']['typology']);
        $this->assertSame('Carrer Major, 1 — 43870 Amposta (Tarragona), España', $processed['statement_of_responsibility']['vendor_address']);
    }

    private function processConfiguration(array $config): array
    {
        $treeBuilder = new TreeBuilder('flexible_ux_verifactu');
        $loader = new DefinitionFileLoader($treeBuilder, new FileLocator(dirname(__DIR__, 2).'/config'));
        $loader->load('definition.php');

        return (new Processor())->process($treeBuilder->buildTree(), [$config]);
    }
}
