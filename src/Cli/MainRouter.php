<?php

declare(strict_types=1);

namespace Devkit\Env\Cli;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
final readonly class MainRouter
{
    private const int EXIT_OK = 0;

    private const int EXIT_ERROR = 2;

    /**
     * @param list<string> $argv
     */
    public function run(array $argv): int
    {
        array_shift($argv);

        if ($argv === []) {
            $this->printGlobalHelp();

            return self::EXIT_OK;
        }

        $first = $argv[0];
        if ($first === '-h' || $first === '--help') {
            $this->printGlobalHelp();

            return self::EXIT_OK;
        }

        if ($first === 'help') {
            $this->printGlobalHelp();

            return self::EXIT_OK;
        }

        if ($first === 'diff' || str_starts_with($first, '-')) {
            if ($first === 'diff') {
                array_shift($argv);
            }

            return (new DiffCommand())->run($argv);
        }

        if ($first === 'save') {
            return (new SaveCommand())->run(array_slice($argv, 1));
        }

        if ($first === 'use') {
            return (new UseCommand())->run(array_slice($argv, 1));
        }

        if ($first === 'list') {
            return (new ListCommand())->run(array_slice($argv, 1));
        }

        if ($first === 'delete' || $first === 'rm') {
            return (new DeleteCommand())->run(array_slice($argv, 1));
        }

        fwrite(STDERR, sprintf("Unknown command: %s\nRun devkit-env --help\n", $first));

        return self::EXIT_ERROR;
    }

    private function printGlobalHelp(): void
    {
        echo <<<'TXT'
devkit-env — switch between saved .env profiles and compare environments.

Commands:
  diff    Compare .env files (drift report). Run: devkit-env diff --help
  save    Save a .env file into a named profile under ./env/ (copy from --from or current target)
  use     Apply a named profile over your working .env (with backup by default)
  list    List saved profile names
  delete  Remove a saved profile (alias: rm)

Configuration (optional): .devkit-env.json in the project root
  storeDir, backupDir, defaultEnv (or targetEnv), afterSwitch, afterSwitchProfiles — see README.

Examples:
  devkit-env save --name staging --from .env.staging
  devkit-env use staging
  devkit-env diff --baseline=local --env local=.env --env prod=.env.prod

TXT;
    }
}
