<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformEncoreBundle\Composer;

use Composer\Script\Event;
use EzSystems\EzPlatformEncoreBundle\Command\CompileAssetsCommand;
use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Composer\Util\ProcessExecutor;

/**
 * Runs assets compilation command in separate process.
 *
 * Code is adapted from {@see \Sensio\Bundle\DistributionBundle\Composer\ScriptHandler}.
 */
class ScriptHandler
{
    /**
     * @param \Composer\Script\Event $event
     */
    public static function compileAssets(Event $event): void
    {
        $options = $event->getComposer()->getPackage()->getExtra();
        $symfonyBinDir = $options['symfony-bin-dir'];
        $timeout = $event->getComposer()->getConfig()->get('process-timout');

        $php = ProcessExecutor::escape(self::getPhpExecutable());
        $console = ProcessExecutor::escape("{$symfonyBinDir}/console");
        if ($event->getIO()->isDecorated()) {
            $console .= ' --ansi';
        }

        if (!$event->isDevMode()) {
            $console .= ' --env=prod';
        }

        $process = new Process(
            "{$php} {$console} " . CompileAssetsCommand::COMMAND_NAME,
            null,
            null,
            null,
            $timeout
        );
        $process->run(function ($type, $buffer) use ($event) {
            $event->getIO()->write($buffer, false);
        });

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                "An error occurred when executing the \"%s\" command:\n\n%s\n\n%s",
                ProcessExecutor::escape(CompileAssetsCommand::COMMAND_NAME),
                self::removeDecoration($process->getOutput()),
                self::removeDecoration($process->getErrorOutput()))
            );
        }
    }

    private static function removeDecoration(string $text): string
    {
        return preg_replace("/\033\[[^m]*m/", '', $text);
    }

    private static function getPhpExecutable(): string
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find(false)) {
            throw new RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        return $phpPath;
    }
}
