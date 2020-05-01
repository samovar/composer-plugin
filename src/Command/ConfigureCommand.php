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

        // Docker config
        if ($this->getIO()->askConfirmation('Add the default docker config? [Y/n]?')) {
            $projectName = $this->getIO()->ask('Please set the default project name for the docker config (e.g. "myproject")');
            $dockerPort = $this->getIO()->ask('Please specify the docker port (random 48XXX would be used by default)', (string) \random_int(48000, 48999));
            $dockerResourceDir = \sprintf('%s%sdocker', $resourceDir, DIRECTORY_SEPARATOR);
            $dockerRootDir = \sprintf('%s%sdocker', $rootDir, DIRECTORY_SEPARATOR);
            if (!\file_exists($dockerRootDir)) {
                // recursive copy
                \shell_exec(\sprintf('cp -r %s %s', $dockerResourceDir, $dockerRootDir));

                if (null !== $projectName) {
                    // replace the default project name in the docker config
                    \shell_exec(\sprintf('find %s/dev -name \'*.*\' -type f -exec sed -i \'s/defaultProjectName/%s/\' {} ;', $dockerRootDir, $projectName));
                }
            }

            if (!\file_exists($dockerComposeRootFile = $rootDir.DIRECTORY_SEPARATOR.'.docker-compose.yaml')) {
                \copy($resourceDir.DIRECTORY_SEPARATOR.'.docker-compose.yaml', $dockerComposeRootFile);

                if (null !== $projectName) {
                    // replace the default project name in the docker config
                    $dockerComposeContents = \str_replace('defaultProjectName', $projectName, \file_get_contents($dockerComposeRootFile));
                    \file_put_contents($dockerComposeRootFile, $dockerComposeContents);
                }

                // set the docker port
                $dockerComposeContents = \str_replace('replacePortWithRandomNum', $dockerPort, \file_get_contents($dockerComposeRootFile));
                \file_put_contents($dockerComposeRootFile, $dockerComposeContents);
            }

            if (!\file_exists($makefileRootFile = $rootDir.DIRECTORY_SEPARATOR.'.Makefile')) {
                \copy($resourceDir.DIRECTORY_SEPARATOR.'.Makefile', $makefileRootFile);

                if (null !== $projectName) {
                    // replace the default project name in Makefile
                    $makefileContents = \str_replace('defaultProjectName', $projectName, \file_get_contents($makefileRootFile));
                    \file_put_contents($makefileRootFile, $makefileContents);
                }
            }
        }
    }
}
