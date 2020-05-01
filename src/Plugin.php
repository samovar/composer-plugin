<?php

declare(strict_types=1);

namespace Samovar\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider as CapabilityCommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        // noop
    }

    public function getCapabilities(): array
    {
        return [
            CapabilityCommandProvider::class => CommandProvider::class,
        ];
    }
}
