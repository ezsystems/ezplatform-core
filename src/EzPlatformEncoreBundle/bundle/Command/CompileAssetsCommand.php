<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformEncoreBundle\Command;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CompileAssetsCommand extends ContainerAwareCommand
{
    public const COMMAND_NAME = 'ezplatform:encore:compile';

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Compiles all assets using WebPack Encore')
            ->addOption(
                'timeout',
                't',
                InputOption::VALUE_OPTIONAL,
                'Timeout in seconds',
                300
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $timeout = $input->getOption('timeout');

        if (!is_numeric($timeout)) {
            throw new InvalidArgumentException('Timeout value has to be an integer.');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $timeout = $input->getOption('timeout');
        $env = $input->getOption('env');

        $output->writeln(sprintf('Compiling all <comment>%s</comment> assets.', $env));
        $output->writeln('');

        $encoreEnv = $env === 'prod' ? 'prod' : 'dev';
        $yarnEncoreCommand = "yarn encore {$encoreEnv}";

        $debugFormatter = $this->getHelper('debug_formatter');

        $process = new Process(
            $yarnEncoreCommand,
            null,
            null,
            null,
            $timeout
        );

        $output->writeln($debugFormatter->start(
            spl_object_hash($process),
            sprintf('Evaluating command <comment>%s</comment>', $yarnEncoreCommand)
        ));

        $process->run(static function ($type, $buffer) use ($output, $debugFormatter, $process) {
            $output->write(
                $debugFormatter->progress(
                    spl_object_hash($process),
                    $buffer,
                    Process::ERR === $type
                )
            );
        });

        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf("An error occurred when executing the \"%s\" command:\n\n%s\n\n%s", $yarnEncoreCommand, $process->getOutput(), $process->getErrorOutput()));
        }

        $output->writeln(
            $debugFormatter->stop(
                spl_object_hash($process),
                'Command finished',
                $process->isSuccessful()
            )
        );
    }
}
