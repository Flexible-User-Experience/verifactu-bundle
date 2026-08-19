<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Tests\Command;

use FlexibleUx\VerifactuBundle\Command\GenerateSifStatementCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class GenerateSifStatementCommandTest extends TestCase
{
    private const STATEMENT_CONFIG = [
        'composition' => [
            'Módulo de facturación',
            'Biblioteca josemmo/verifactu-php',
        ],
        'functionalities' => [
            'Generación del código QR tributario',
        ],
        'installation_characteristics' => [
            'Instalación SaaS sobre servidor propio en la UE',
        ],
        'typology' => 'Sistema informático de facturación de uso propio',
        'vendor_address' => 'Carrer Major, 1 — 43870 Amposta (Tarragona), España',
    ];
    private const EMPTY_STATEMENT_CONFIG = [
        'composition' => [],
        'functionalities' => [],
        'installation_characteristics' => [],
        'typology' => null,
        'vendor_address' => null,
    ];

    private string $outputFilepath;

    protected function setUp(): void
    {
        $this->outputFilepath = sys_get_temp_dir().'/sif-statement-test.md';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->outputFilepath)) {
            unlink($this->outputFilepath);
        }
    }

    public function testGeneratesStatementDocument(): void
    {
        $tester = $this->makeCommandTester(onlySupportsVerifactu: true);
        $tester->execute(['place' => 'Barcelona', '--signed-at' => '2026-08-14']);
        $tester->assertCommandIsSuccessful();
        $display = $tester->getDisplay();
        $this->assertStringContainsString('# Declaración responsable del sistema informático de facturación', $display);
        $this->assertStringContainsString('- **Nombre o razón social:** Vendor Name SL', $display);
        $this->assertStringContainsString('- **NIF:** B12345678', $display);
        $this->assertStringContainsString('- **Nombre del sistema:** My Software SIF', $display);
        $this->assertStringContainsString('- **Versión:** 1.0.0', $display);
        $this->assertStringContainsString('Emisión de facturas verificables ("VERI*FACTU") como única modalidad de funcionamiento', $display);
        $this->assertStringContainsString('el sistema no permite su uso por varios obligados tributarios', $display);
        $this->assertStringContainsString('Real Decreto 1007/2023', $display);
        $this->assertStringContainsString('Orden HAC/1177/2024', $display);
        $this->assertStringContainsString('En Barcelona, a 14/08/2026', $display);
    }

    public function testGeneratesDualModeStatementDocument(): void
    {
        $tester = $this->makeCommandTester(onlySupportsVerifactu: false);
        $tester->execute(['place' => 'Girona', '--signed-at' => '2026-08-14']);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('funcionamiento en modalidad de no remisión de los registros de facturación', $tester->getDisplay());
    }

    public function testRendersTheArt13MandatoryContent(): void
    {
        $tester = $this->makeCommandTester(onlySupportsVerifactu: true);
        $tester->execute(['place' => 'Amposta', '--signed-at' => '2026-08-14']);
        $tester->assertCommandIsSuccessful();
        $display = $tester->getDisplay();
        $this->assertStringContainsString('- **Datos de localización:** Carrer Major, 1 — 43870 Amposta (Tarragona), España', $display);
        $this->assertStringContainsString('### 2.1. Tipología', $display);
        $this->assertStringContainsString('Sistema informático de facturación de uso propio', $display);
        $this->assertStringContainsString('### 2.2. Composición', $display);
        $this->assertStringContainsString('- Módulo de facturación', $display);
        $this->assertStringContainsString('- Biblioteca josemmo/verifactu-php', $display);
        $this->assertStringContainsString('### 2.3. Funcionalidades', $display);
        $this->assertStringContainsString('- Generación del código QR tributario', $display);
        $this->assertStringContainsString('## 3. Características de la instalación', $display);
        $this->assertStringContainsString('- **Número de instalación:** INST-001', $display);
        $this->assertStringContainsString('- **Obligados tributarios que la utilizan:** uno', $display);
        $this->assertStringContainsString('- Instalación SaaS sobre servidor propio en la UE', $display);
        $this->assertStringNotContainsString('[PENDIENTE DE COMPLETAR]', $display);
        $this->assertStringNotContainsString('The generated draft is incomplete', $display);
    }

    public function testMarksTheMissingArt13MandatoryContentAsPending(): void
    {
        $tester = $this->makeCommandTester(onlySupportsVerifactu: true, statementConfig: self::EMPTY_STATEMENT_CONFIG);
        $tester->execute(['place' => 'Amposta', '--signed-at' => '2026-08-14']);
        $tester->assertCommandIsSuccessful();
        $display = $tester->getDisplay();
        foreach (['vendor_address', 'typology', 'composition', 'functionalities', 'installation_characteristics'] as $configKey) {
            $this->assertStringContainsString(\sprintf('**[PENDIENTE DE COMPLETAR]** _rellene la opción de configuración `flexible_ux_verifactu.statement_of_responsibility.%s`._', $configKey), $display);
            $this->assertStringContainsString(\sprintf('  * flexible_ux_verifactu.statement_of_responsibility.%s', $configKey), $display);
        }
        $this->assertStringContainsString('The generated draft is incomplete', $display);
        $this->assertStringContainsString('- **Número de instalación:** INST-001', $display);
    }

    public function testWarnsAboutTheMissingArt13MandatoryContentThroughTheErrorOutput(): void
    {
        $tester = $this->makeCommandTester(onlySupportsVerifactu: true, statementConfig: self::EMPTY_STATEMENT_CONFIG);
        $tester->execute(['place' => 'Amposta', '--signed-at' => '2026-08-14'], ['capture_stderr_separately' => true]);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('The generated draft is incomplete', $tester->getErrorOutput());
        $this->assertStringNotContainsString('The generated draft is incomplete', $tester->getDisplay());
    }

    public function testWritesStatementDocumentToFile(): void
    {
        $tester = $this->makeCommandTester(onlySupportsVerifactu: true);
        $tester->execute(['place' => 'Barcelona', '--signed-at' => '2026-08-14', '--output' => $this->outputFilepath]);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('review it carefully before signing', $tester->getDisplay());
        $this->assertFileExists($this->outputFilepath);
        $this->assertStringContainsString('# Declaración responsable', (string) file_get_contents($this->outputFilepath));
    }

    private function makeCommandTester(bool $onlySupportsVerifactu, array $statementConfig = self::STATEMENT_CONFIG): CommandTester
    {
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 2).'/templates', 'FlexibleUxVerifactu');

        return new CommandTester(new GenerateSifStatementCommand(
            [
                'vendor_name' => 'Vendor Name SL',
                'vendor_nif' => 'B12345678',
                'name' => 'My Software SIF',
                'id' => '01',
                'version' => '1.0.0',
                'installation_number' => 'INST-001',
                'only_supports_verifactu' => $onlySupportsVerifactu,
                'supports_multiple_taxpayers' => false,
                'has_multiple_taxpayers' => false,
            ],
            $statementConfig,
            new Environment($loader)
        ));
    }
}
