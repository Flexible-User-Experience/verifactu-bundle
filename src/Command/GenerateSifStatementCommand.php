<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

#[AsCommand(
    name: 'flexible-ux:verifactu:generate-sif-statement',
    description: 'Generate a draft SIF statement of responsibility document ("declaración responsable", Art. 13 RD 1007/2023) from the configured computer system',
)]
final class GenerateSifStatementCommand extends Command
{
    /**
     * Content required by the Art. 13 RD 1007/2023 that no AEAT record carries, so it can only be filled through the statement_of_responsibility config options.
     */
    private const MANDATORY_STATEMENT_CONFIG_KEYS = [
        'vendor_address',
        'typology',
        'composition',
        'functionalities',
        'installation_characteristics',
    ];

    public function __construct(
        private readonly array $computerSystemConfig,
        private readonly array $statementConfig,
        private readonly Environment $twig,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('place', InputArgument::REQUIRED, 'Place where the statement is signed (e.g. "Barcelona")')
            ->addOption('signed-at', null, InputOption::VALUE_REQUIRED, 'Signature date in "YYYY-MM-DD" format, defaults to today')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write the Markdown document to this file instead of printing it')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $document = $this->twig->render('@FlexibleUxVerifactu/statement_of_responsibility.md.twig', [
            'computer_system' => $this->computerSystemConfig,
            'statement' => $this->statementConfig,
            'place' => $input->getArgument('place'),
            'signed_at' => new \DateTimeImmutable((string) ($input->getOption('signed-at') ?? 'now')),
        ]);
        $outputFilepath = $input->getOption('output');
        if (null !== $outputFilepath) {
            (new Filesystem())->dumpFile($outputFilepath, $document);
            $output->writeln(\sprintf('SIF statement of responsibility written to "%s", review it carefully before signing.', $outputFilepath));
        } else {
            $output->write($document);
        }
        $this->warnAboutMissingStatementConfigKeys($output);

        return Command::SUCCESS;
    }

    private function warnAboutMissingStatementConfigKeys(OutputInterface $output): void
    {
        $missingKeys = $this->collectMissingStatementConfigKeys();
        if ([] === $missingKeys) {
            return;
        }
        $errorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $errorOutput->writeln('<comment>The generated draft is incomplete, the Art. 13 RD 1007/2023 requires the missing content of these config options:</comment>');
        foreach ($missingKeys as $missingKey) {
            $errorOutput->writeln(\sprintf('<comment>  * flexible_ux_verifactu.statement_of_responsibility.%s</comment>', $missingKey));
        }
    }

    /**
     * @return string[]
     */
    private function collectMissingStatementConfigKeys(): array
    {
        $missingKeys = [];
        foreach (self::MANDATORY_STATEMENT_CONFIG_KEYS as $configKey) {
            $configValue = $this->statementConfig[$configKey] ?? null;
            if (\is_array($configValue) ? [] === $configValue : '' === trim((string) $configValue)) {
                $missingKeys[] = $configKey;
            }
        }

        return $missingKeys;
    }
}
