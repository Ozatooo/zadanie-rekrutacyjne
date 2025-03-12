<?php

namespace App\Command;

use App\Service\ErrorService;
use App\Service\ProcessService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:process-service-data',
    description: 'Processes a JSON file and classifies elements based on description',
)]
class ProcessServiceReportsCommand extends Command
{
    private ProcessService $processService;
    private ErrorService $errorService;
    private LoggerInterface $logger;

    public function __construct(ProcessService $processService, ErrorService $errorService, LoggerInterface $logger)
    {
        parent::__construct();
        $this->processService = $processService;
        $this->errorService = $errorService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this->addArgument('inputFile', InputArgument::REQUIRED, 'Path to JSON input file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $inputFile = $input->getArgument('inputFile');
        if (!$filesystem->exists($inputFile)) {
            $this->errorService->logErrorWithFile('fileDoNotExist', $inputFile, $io, $this->logger);
            return Command::FAILURE;
        }

        $jsonData = file_get_contents($inputFile);
        $data = json_decode($jsonData, true);

        if ($data === null) {
            $this->errorService->logErrorWithFile('jsonParsingError', $inputFile, $io, $this->logger);
            return Command::FAILURE;
        }

        $invalidEntries = [];
        $seenDescriptions = [];
        $reports = [];
        foreach ($data as $entry) {
            if (!isset($entry['description'])) {
                $this->errorService->logError('noDescription', $entry, $io, $this->logger);
                $invalidEntries[] = $entry;
                continue;
            }

            $description = trim($entry['description']);
            if (in_array($description, $seenDescriptions)) {
                $this->errorService->logError('duplicate', $entry, $io, $this->logger);
                $invalidEntries[] = $entry;
                continue;
            }
            $seenDescriptions[] = $description;

            $processedResult = $this->processService->processEntry($entry);
            $reports[$processedResult['type']][] = $processedResult['data'];
        
            $this->logger->info('Successfully processed entry', $processedResult);
        }

        $filesystem->dumpFile('var/data/inspections.json', json_encode($reports['inspections'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $filesystem->dumpFile('var/data/failures.json', json_encode($reports['failures'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $filesystem->dumpFile('var/data/invalid_entries.json', json_encode($invalidEntries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->logger->info('Files saved: inspections.json, failures.json, invalid_entries.json');
        
        $io->success('Files saved: inspections.json, failures.json, invalid_entries.json');
        $io->text([
            "Number of messages processed: " . count($data),
            "Number of inspections created: " . count($reports['inspections']),
            "Number of failure reports: " . count($reports['failures']),
            "Number of invalid entries (including duplicates): " . count($invalidEntries),
        ]);

        return Command::SUCCESS;
    }
}
