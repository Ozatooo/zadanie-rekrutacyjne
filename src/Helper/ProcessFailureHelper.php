<?php

namespace App\Helper;

class ProcessFailureHelper
{
    public static function determinePriority(string $description): string
    {
        return match (true) {
            str_contains($description, 'bardzo pilne') => 'krytyczny',
            str_contains($description, 'pilne') => 'wysoki',
            default => 'normalny',
        };
    }
}