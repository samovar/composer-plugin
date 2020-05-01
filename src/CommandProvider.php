<?php

declare(strict_types=1);

namespace Samovar\Composer;

use Composer\Plugin\Capability\CommandProvider as CapabilityCommandProvider;
use Samovar\Composer\Command\ConfigureCommand;

class CommandProvider implements CapabilityCommandProvider
{
    public function getCommands(): array
    {
        return [
            new ConfigureCommand(),
        ];
    }
}
