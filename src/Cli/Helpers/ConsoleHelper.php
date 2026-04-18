<?php

declare(strict_types=1);

namespace Devkit\Env\Cli\Helpers;

final class ConsoleHelper
{
    public static function isInteractive(): bool
    {
        if (!function_exists('posix_isatty')) {
            return false;
        }

        return posix_isatty(STDIN) && posix_isatty(STDOUT);
    }

    public static function prompt(string $message): string
    {
        echo $message;
        $line = fgets(STDIN);

        return $line === false ? '' : trim($line);
    }
}
