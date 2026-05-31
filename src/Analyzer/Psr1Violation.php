<?php

declare(strict_types=1);

namespace zonuexe\Phfizer\Analyzer;

enum Psr1Violation
{
    case MIXED_SIDE_EFFECTS;

    public static function tryFromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }
}
