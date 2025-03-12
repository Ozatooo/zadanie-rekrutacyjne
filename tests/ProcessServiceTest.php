<?php

namespace App\Tests\Service;

use App\Service\ProcessService;
use PHPUnit\Framework\TestCase;
use DateTimeImmutable;

class ProcessServiceTest extends TestCase
{
    private ProcessService $processService;

    protected function setUp(): void
    {
        $this->processService = new ProcessService();
    }

    public function testProcessInspection()
    {
        $entry = [
            'number' => 100,
            'description' => 'przegląd klimatyzacji',
            'dueDate' => '2025-04-15',
            'phone' => '123456789'
        ];

        $result = $this->processService->processEntry($entry);

        $this->assertEquals('przegląd', $result['data']['type']);
        $this->assertEquals('2025-04-15', $result['data']['dueDate']);
        $this->assertEquals('zaplanowano', $result['data']['status']);
        $this->assertEquals('123456789', $result['data']['contactPhone']);
        $this->assertArrayHasKey('createdAt', $result['data']);
    }

    public function testProcessFailure()
    {
        $entry = [
            'number' => 99,
            'description' => 'Awaria kotła',
            'dueDate' => '2025-04-15',
            'phone' => '987654321'
        ];

        $result = $this->processService->processEntry($entry);

        $this->assertEquals('zgłoszenie awarii', $result['data']['type']);
        $this->assertEquals('2025-04-15', $result['data']['serviceVisitDate']);
        $this->assertEquals('termin', $result['data']['status']);
        $this->assertEquals('987654321', $result['data']['contactPhone']);
        $this->assertArrayHasKey('createdAt', $result['data']);
    }

    public function testDeterminePriority()
    {
        $reflection = new \ReflectionClass($this->processService);
        $method = $reflection->getMethod('determinePriority');
        $method->setAccessible(true);

        $this->assertEquals('krytyczny', $method->invoke($this->processService, 'To jest bardzo pilne zgłoszenie!'));
        $this->assertEquals('wysoki', $method->invoke($this->processService, 'To jest pilne zgłoszenie!'));
        $this->assertEquals('normalny', $method->invoke($this->processService, 'Zgłoszenie standardowe'));
    }
}
