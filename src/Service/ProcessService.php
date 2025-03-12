<?php

namespace App\Service;

use DateTimeImmutable;

class ProcessService
{
    public function processEntry(array $entry): array
    {
        $description = $entry['description'] ?? '';
        $dueDate = isset($entry['dueDate']) ? new \DateTimeImmutable($entry['dueDate']) : null;
        $contactPhone = $entry['phone'] ?? null;
        $priority = $this->determinePriority($description);

        switch (true) {
            case stripos($description, 'przegląd') !== false:
                return [
                    'type' => 'inspections',
                    'data' => $this->processInspection($description, $dueDate, $contactPhone)
                ];

            default:
                return [
                    'type' => 'failures',
                    'data' => $this->processFailure($description, $dueDate, $contactPhone, $priority)
                ];
        }
    }

    private function processInspection(string $description, ?\DateTimeImmutable $dueDate, ?string $contactPhone): array
    {
        return [
            'description' => $description,
            'type' => 'przegląd',
            'dueDate' => $dueDate?->format('Y-m-d'),
            'weekOfYear' => $dueDate ? (int) $dueDate->format('W') : null,
            'status' => $dueDate ? 'zaplanowano' : 'nowy',
            'serviceRecommendations' => '',
            'contactPhone' => $contactPhone,
            'createdAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s')
        ];
    }

    private function processFailure(string $description, ?\DateTimeImmutable $dueDate, ?string $contactPhone, string $priority): array
    {
        return [
            'description' => $description,
            'type' => 'zgłoszenie awarii',
            'priority' => $priority,
            'serviceVisitDate' => $dueDate?->format('Y-m-d'),
            'status' => $dueDate ? 'termin' : 'nowy',
            'serviceNotes' => '',
            'contactPhone' => $contactPhone,
            'createdAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s')
        ];
    }

    private function determinePriority(string $description): string
    {
        return match (true) {
            str_contains($description, 'bardzo pilne') => 'krytyczny',
            str_contains($description, 'pilne') => 'wysoki',
            default => 'normalny',
        };
    }
}
