<?php

declare(strict_types=1);

namespace Samovar\Composer\Command;

use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ConfigureCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('samovar:configure')
            ->setAliases(['configure'])
            ->addOption('no-backup', null, InputOption::VALUE_NONE)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var Composer $composer */
        $composer = $this->getComposer();

        /** @var Application $application */
        $application = $this->getApplication();

        $rootPackage = $composer->getPackage();

        $extra = $rootPackage->getExtra();

        $filesystem = new Filesystem();

        $rootDir = \realpath($extra['symfony']['root-dir'] ?? '.');

        $resourceDir = \dirname(__DIR__).DIRECTORY_SEPARATOR.'Resources';

        $requireCommand = $application->find('require');

        $requireCommand->run(new ArrayInput([
            'packages' => [
                'roave/security-advisories:dev-master',
                'friendsofphp/php-cs-fixer',
                'liip/test-fixtures-bundle',
                'phpstan/extension-installer',
                'phpstan/phpstan',
                'phpstan/phpstan-doctrine',
                'phpstan/phpstan-phpunit',
                'phpstan/phpstan-symfony',
            ],
            '--dev' => true,
        ]), $output);

        $finder = new Finder();
        $finder
            ->in($resourceDir)
            ->ignoreVCS(true)
            ->ignoreDotFiles(false)
            ->files()
        ;

        $backup = !$input->getOption('no-backup');

        foreach ($finder as $file) {
            $filename = $file->getRelativePathname();
            $originFile = $resourceDir.DIRECTORY_SEPARATOR.$filename;
            $targetFile = $rootDir.DIRECTORY_SEPARATOR.$filename;

            if ($backup && $filesystem->exists($targetFile)) {
                $filesystem->copy($targetFile, $targetFile.'.back', true);
            }

            $filesystem->copy($originFile, $targetFile, true);
        }
    }
}
