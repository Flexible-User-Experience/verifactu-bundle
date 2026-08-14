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
    private string $outputFilepath;

    protected function setUp(): void
    {
        $this->outputFilepath = sys_get_temp_dir().'/sif-statement-test.txt';
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
        $this->assertStringContainsString('DECLARACIÓN RESPONSABLE DEL SISTEMA INFORMÁTICO DE FACTURACIÓN', $display);
        $this->assertStringContainsString('Vendor Name SL', $display);
        $this->assertStringContainsString('B12345678', $display);
        $this->assertStringContainsString('My Software SIF', $display);
        $this->assertStringContainsString('Versión: 1.0.0', $display);
        $this->assertStringContainsString('únicamente como sistema de emisión de facturas', $display);
        $this->assertStringContainsString('no permite su uso por varios obligados tributarios', $display);
        $this->assertStringContainsString('Real Decreto 1007/2023', $display);
        $this->assertStringContainsString('Orden HAC/1177/2024', $display);
        $this->assertStringContainsString('En Barcelona, a 14/08/2026', $display);
    }

    public function testGeneratesDualModeStatementDocument(): void
    {
        $tester = $this->makeCommandTester(onlySupportsVerifactu: false);
        $tester->execute(['place' => 'Girona', '--signed-at' => '2026-08-14']);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('El sistema puede funcionar tanto como sistema', $tester->getDisplay());
    }

    public function testWritesStatementDocumentToFile(): void
    {
        $tester = $this->makeCommandTester(onlySupportsVerifactu: true);
        $tester->execute(['place' => 'Barcelona', '--signed-at' => '2026-08-14', '--output' => $this->outputFilepath]);
        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('review it carefully before signing', $tester->getDisplay());
        $this->assertFileExists($this->outputFilepath);
        $this->assertStringContainsString('DECLARACIÓN RESPONSABLE', (string) file_get_contents($this->outputFilepath));
    }

    private function makeCommandTester(bool $onlySupportsVerifactu): CommandTester
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
            new Environment($loader)
        ));
    }
}
