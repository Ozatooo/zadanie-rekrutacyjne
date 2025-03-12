<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Style\SymfonyStyle;

class ErrorService
{
    public function logError(string $type, array $entry, SymfonyStyle $io, $logger): void
    {
        $reason = '';
        switch($type)
        {
            case 'duplicate':
                $reason = 'Duplicate entry detected';
                break;
            case 'noDescription':
                $reason = 'Entry missing description';
                break;
            default:
                $reason = 'Unexpected error';
                break;
        }

        $logger->warning($reason, $entry);
        $io->error('Number: ' . $entry['number'] . ' was not saved. Reason: ' . $reason . '.');
    }

    public function logErrorWithFile(string $type, string $inputFile, SymfonyStyle $io, LoggerInterface $logger): void
    {
        $reason = '';
        switch($type)
        {
            case 'fileDoNotExist':
                $reason = 'File does not exist!';
                break;
            case 'jsonParsingError':
                $reason = 'JSON parsing error!';
                break;
            default:
                $reason = 'Unexpected error!';
                break;
        }

        $io->error($reason);
        $logger->error($reason . ' ' . $inputFile);
    }
}
