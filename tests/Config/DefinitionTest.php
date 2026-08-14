<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Tests\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
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

    private function processConfiguration(array $config): array
    {
        $treeBuilder = new TreeBuilder('flux_verifactu');
        $loader = new DefinitionFileLoader($treeBuilder, new FileLocator(dirname(__DIR__, 2).'/config'));
        $loader->load('definition.php');

        return (new Processor())->process($treeBuilder->buildTree(), [$config]);
    }
}
