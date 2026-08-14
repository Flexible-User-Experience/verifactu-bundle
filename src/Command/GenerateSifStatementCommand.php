<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

#[AsCommand(
    name: 'flux:verifactu:generate-sif-statement',
    description: 'Generate a draft SIF statement of responsibility document ("declaración responsable", Art. 13 RD 1007/2023) from the configured computer system',
)]
final class GenerateSifStatementCommand extends Command
{
    public function __construct(
        private readonly array $computerSystemConfig,
        private readonly Environment $twig,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('place', InputArgument::REQUIRED, 'Place where the statement is signed (e.g. "Barcelona")')
            ->addOption('signed-at', null, InputOption::VALUE_REQUIRED, 'Signature date in "YYYY-MM-DD" format, defaults to today')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write the document to this file instead of printing it')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $document = $this->twig->render('@FluxVerifactu/statement_of_responsibility.txt.twig', [
            'computer_system' => $this->computerSystemConfig,
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

        return Command::SUCCESS;
    }
}
