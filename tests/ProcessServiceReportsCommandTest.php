<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use App\Command\ProcessServiceReportsCommand;
use App\Service\ProcessService;
use App\Service\ErrorService;
use Psr\Log\LoggerInterface;

class ProcessServiceReportsCommandTest extends KernelTestCase
{
    private string $testFilePath;

    protected function setUp(): void
    {
        $this->testFilePath = sys_get_temp_dir() . '/test_input.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
        if (file_exists('var/data/inspections.json')) {
            unlink('var/data/inspections.json');
        }
        if (file_exists('var/data/failures.json')) {
            unlink('var/data/failures.json');
        }
        if (file_exists('var/data/invalid_entries.json')) {
            unlink('var/data/invalid_entries.json');
        }
    }

    public function testCommandExecution()
    {
        self::bootKernel();
        $application = new Application();
        $application->add(new ProcessServiceReportsCommand(
            self::getContainer()->get(ProcessService::class),
            self::getContainer()->get(ErrorService::class),
            self::getContainer()->get(LoggerInterface::class)
        ));

        $testData = [
            ['number' => 1, 'description' => 'przegląd wentylacji', 'dueDate' => '2025-04-20', 'phone' => '111222333'],
            ['number' => 2, 'description' => 'Awaria ogrzewania', 'phone' => '444555666'],
            ['number' => 3, 'description' => 'przegląd wentylacji', 'dueDate' => '2025-04-20', 'phone' => '111222333'],
            ['number' => 4, 'phone' => '777888999'],
        ];

        file_put_contents($this->testFilePath, json_encode($testData));

        $command = $application->find('app:process-service-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['inputFile' => $this->testFilePath]);

        $output = $commandTester->getDisplay();

        $this->assertEquals(0, $commandTester->getStatusCode());

        $this->assertFileExists('var/data/inspections.json');
        $this->assertFileExists('var/data/failures.json');
        $this->assertFileExists('var/data/invalid_entries.json');

        $inspections = json_decode(file_get_contents('var/data/inspections.json'), true);
        $failures = json_decode(file_get_contents('var/data/failures.json'), true);
        $invalidEntries = json_decode(file_get_contents('var/data/invalid_entries.json'), true);

        $this->assertCount(1, $inspections);
        $this->assertCount(1, $failures);
        $this->assertCount(2, $invalidEntries);

        $this->assertStringContainsString('Files saved: inspections.json, failures.json, invalid_entries.json', $output);
        $this->assertStringContainsString('Number of messages processed: 4', $output);
        $this->assertStringContainsString('Number of inspections created: 1', $output);
        $this->assertStringContainsString('Number of failure reports: 1', $output);
        $this->assertStringContainsString('Number of invalid entries (including duplicates): 2', $output);
    }

    public function testCommandFailsIfFileDoesNotExist()
    {
        self::bootKernel();
        $application = new Application();
        $application->add(new ProcessServiceReportsCommand(
            self::getContainer()->get(ProcessService::class),
            self::getContainer()->get(ErrorService::class),
            self::getContainer()->get(LoggerInterface::class)
        ));

        $command = $application->find('app:process-service-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['inputFile' => '/nonexistent/file.json']);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('File does not exist!', $commandTester->getDisplay());
    }

    public function testCommandFailsIfJsonIsInvalid()
    {
        self::bootKernel();
        file_put_contents($this->testFilePath, 'invalid json');

        $application = new Application();
        $application->add(new ProcessServiceReportsCommand(
            self::getContainer()->get(ProcessService::class),
            self::getContainer()->get(ErrorService::class),
            self::getContainer()->get(LoggerInterface::class)
        ));

        $command = $application->find('app:process-service-data');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['inputFile' => $this->testFilePath]);

        $this->assertEquals(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('JSON parsing error!', $commandTester->getDisplay());
    }
}
