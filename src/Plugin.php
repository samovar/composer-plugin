<?php

declare(strict_types=1);

namespace Samovar\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Samovar\Composer\Command\ConfigureCommand;
use Symfony\Component\Console\Application;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var bool
     */
    private static $activated;

    public function activate(Composer $composer, IOInterface $io): void
    {
        if (self::$activated) {
            return;
        }

        $backtrace = \debug_backtrace();
        foreach ($backtrace as $trace) {
            if (!isset($trace['object'])) {
                continue;
            }

            if (!$trace['object'] instanceof Application) {
                continue;
            }

            /** @var Application $app */
            $app = $trace['object'];

            $app->add(new ConfigureCommand());

            break;
        }

        self::$activated = true;
    }

    public static function getSubscribedEvents(): array
    {
        if (!self::$activated) {
            return [];
        }

        return [
        ];
    }
}
