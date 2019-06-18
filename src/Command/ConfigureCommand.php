<?php

declare(strict_types=1);

namespace Samovar\Composer\Command;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigureCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('samovar:configure')
            ->setAliases(['configure'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $extra = $this->getComposer()->getPackage()->getExtra();
        $rootDir = \realpath($extra['symfony']['root-dir'] ?? '.');
        $resourceDir = \dirname(__DIR__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR;
        $gitignore = $rootDir.DIRECTORY_SEPARATOR.'.gitignore';

        $requires = $this->getComposer()->getPackage()->getDevRequires();
        $requireCommand = $this->getApplication()->find('require');
        $removeCommand = $this->getApplication()->find('remove');
        $flexInstalled = \class_exists('Symfony\Flex\Flex');

        // Idea
        if (\file_exists($gitignore)) {
            $lines = \file($gitignore, FILE_SKIP_EMPTY_LINES);

            $ideaIgnored = false;
            foreach ($lines as $line) {
                if (false !== \strpos($line, '/.idea'.PHP_EOL) || false !== \strpos($line, '.idea'.PHP_EOL)) {
                    $ideaIgnored = true;
                    break;
                }
            }

            if (!$ideaIgnored && $this->getIO()->askConfirmation('Add ".idea" to .gitignore? [Y/n]?')) {
                \file_put_contents($gitignore, \implode(PHP_EOL, [
                    '',
                    '.idea',
                    '',
                ]), FILE_APPEND);
            }
        }

        // Editorconfig
        if (!\file_exists($rootDir.DIRECTORY_SEPARATOR.'.editorconfig')) {
            \copy($resourceDir.DIRECTORY_SEPARATOR.'.editorconfig', $rootDir.DIRECTORY_SEPARATOR.'.editorconfig');
        }

        // PHP Code Standard Fixer
        if (!\array_key_exists('friendsofphp/php-cs-fixer', $requires)) {
            $requireCommand->run(new ArrayInput([
                'packages' => ['friendsofphp/php-cs-fixer'],
                '--dev' => true,
            ]), $output);

            if (!\file_exists($rootDir.DIRECTORY_SEPARATOR.'.php_cs.dist')) {
                \copy($resourceDir.DIRECTORY_SEPARATOR.'.php_cs.dist', $rootDir.DIRECTORY_SEPARATOR.'.php_cs.dist');
            }

            if (!$flexInstalled && \file_exists($gitignore) && $this->getIO()->askConfirmation('Update .gitignore [Y/n]?')) {
                \file_put_contents($gitignore, \implode(PHP_EOL, [
                    '',
                    '.php_cs',
                    '.php_cs.cache',
                    '',
                ]), FILE_APPEND);
            }
        }

        // Security advisories
        if (!\array_key_exists('roave/security-advisories', $requires)) {
            $requireCommand->run(new ArrayInput([
                'packages' => ['roave/security-advisories:dev-master'],
                '--dev' => true,
            ]), $output);
        }

        //  Symfony phpunit bridge
        if (!\array_key_exists('symfony/phpunit-bridge', $requires)) {
            $removeCommand->run(new ArrayInput([
                'packages' => ['phpunit/phpunit'],
            ]), $output);
            $requireCommand->run(new ArrayInput([
                'packages' => ['symfony/phpunit-bridge'],
                '--dev' => true,
            ]), $output);
        }
    }
}
