<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Platform\Assets\Event\Subscriber;

use Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class AssetsInstallCommandSubscriber implements EventSubscriberInterface
{
    private const ORIGIN_FILE = '/vendors/webalys/streamlineicons/all-icons.svg';
    private const TARGET_FILE = '/img/ez-icons.svg';
    private const RESOURCES_PUBLIC_DIR = '/Resources/public';

    /** @var \Symfony\Component\Filesystem\Filesystem */
    private $filesystem;

    /** @var \Symfony\Component\HttpKernel\KernelInterface */
    private $kernel;

    /** @var string */
    private $projectDir;

    public function __construct(Filesystem $filesystem, KernelInterface $kernel, string $projectDir)
    {
        $this->filesystem = $filesystem;
        $this->kernel = $kernel;
        $this->projectDir = $projectDir;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::TERMINATE => 'onConsoleCommand',
        ];
    }

    public function onConsoleCommand(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();

        if (!$command instanceof AssetsInstallCommand) {
            return;
        }

        if ($event->getExitCode() === Command::FAILURE) {
            return;
        }

        $input = $event->getInput();
        $expectedMethod = $this->getExpectedMethod($input);
        $targetArg = $this->getTarget($input);
        $bundlesDir = $targetArg . '/bundles/';
        $bundles = $this->kernel->getBundles();

        if (!isset($bundles['EzPlatformAdminUiBundle'], $bundles['EzPlatformAdminUiAssetsBundle'])) {
            return;
        }

        if (AssetsInstallCommand::METHOD_RELATIVE_SYMLINK === $expectedMethod) {
            $this->relativeSymlinkWithFallback($bundles, $bundlesDir);
        } elseif (AssetsInstallCommand::METHOD_ABSOLUTE_SYMLINK === $expectedMethod) {
            $this->absoluteSymlinkWithFallback($bundles, $bundlesDir);
        } else {
            $this->hardCopy($bundles, $bundlesDir);
        }
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     */
    private function relativeSymlinkWithFallback(array $bundles, string $bundlesDir): void
    {
        $originFile = $this->getOriginFileForSymlink($bundles);
        $targetFile = $this->getTargetFileForSymlink($bundles);

        try {
            $this->symlink($originFile, $targetFile, true);
        } catch (IOException $e) {
            $this->absoluteSymlinkWithFallback($bundles, $bundlesDir);
        }
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     */
    private function absoluteSymlinkWithFallback(array $bundles, string $bundlesDir): void
    {
        $originFile = $this->getOriginFileForSymlink($bundles);
        $targetFile = $this->getTargetFileForSymlink($bundles);

        try {
            $this->symlink($originFile, $targetFile);
        } catch (IOException $e) {
            $this->hardCopy($bundles, $bundlesDir);
        }
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     */
    private function hardCopy(array $bundles, string $bundlesDir): void
    {
        $originFile = $this->getOriginFileForCopy($bundles, $bundlesDir);
        $targetFile = $this->getTargetFileForCopy($bundles, $bundlesDir);

        $this->filesystem->remove($targetFile);
        $this->filesystem->copy($originFile, $targetFile, true);
    }

    private function symlink(string $originFile, string $targetFile, bool $relative = false): void
    {
        if ($relative) {
            $originFile = $this->filesystem->makePathRelative(\dirname($originFile), \dirname($targetFile)) . basename(self::ORIGIN_FILE);
        }

        $this->filesystem->remove($targetFile);
        $this->filesystem->symlink($originFile, $targetFile);
        if (!file_exists($targetFile)) {
            throw new IOException(sprintf('Symbolic link "%s" was created but appears to be broken.', $targetFile), 0, null, $targetFile);
        }
    }

    private function getPublicDirectory(ContainerInterface $container): string
    {
        $defaultPublicDir = 'public';

        if (null === $this->projectDir && !$container->hasParameter('kernel.project_dir')) {
            return $defaultPublicDir;
        }

        $composerFilePath = ($this->projectDir ?? $container->getParameter('kernel.project_dir')) . '/composer.json';

        if (!file_exists($composerFilePath)) {
            return $defaultPublicDir;
        }

        $composerConfig = json_decode(file_get_contents($composerFilePath), true);

        if (isset($composerConfig['extra']['public-dir'])) {
            return $composerConfig['extra']['public-dir'];
        }

        return $defaultPublicDir;
    }

    private function getTarget(InputInterface $input): string
    {
        $targetArg = $input->getArgument('target');
        $targetArg = rtrim($targetArg ?? '', '/');

        if (empty($targetArg)) {
            $targetArg = $this->getPublicDirectory($this->kernel->getContainer());
        }

        if (!is_dir($targetArg)) {
            $targetArg = $this->kernel->getProjectDir() . '/' . $targetArg;

            if (!is_dir($targetArg)) {
                throw new InvalidArgumentException(sprintf('The target directory "%s" does not exist.', $targetArg));
            }
        }

        return $targetArg;
    }

    private function getExpectedMethod(InputInterface $input): string
    {
        if ($input->getOption('relative')) {
            $expectedMethod = AssetsInstallCommand::METHOD_RELATIVE_SYMLINK;
        } elseif ($input->getOption('symlink')) {
            $expectedMethod = AssetsInstallCommand::METHOD_ABSOLUTE_SYMLINK;
        } else {
            $expectedMethod = AssetsInstallCommand::METHOD_COPY;
        }

        return $expectedMethod;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     */
    private function getOriginFileForSymlink(array $bundles): string
    {
        $adminUiAssetsBundle = $bundles['EzPlatformAdminUiAssetsBundle'];

        return $adminUiAssetsBundle->getPath() . self::RESOURCES_PUBLIC_DIR . self::ORIGIN_FILE;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     */
    private function getTargetFileForSymlink(array $bundles): string
    {
        $adminUiBundle = $bundles['EzPlatformAdminUiBundle'];

        return $adminUiBundle->getPath() . self::RESOURCES_PUBLIC_DIR . self::TARGET_FILE;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     */
    private function getOriginFileForCopy(array $bundles, string $bundlesDir): string
    {
        $adminUiAssetsBundle = $bundles['EzPlatformAdminUiAssetsBundle'];
        $adminUiAssetsDir = preg_replace('/bundle$/', '', strtolower($adminUiAssetsBundle->getName()));

        return $bundlesDir . $adminUiAssetsDir . self::ORIGIN_FILE;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface[] $bundles
     */
    private function getTargetFileForCopy(array $bundles, string $bundlesDir): string
    {
        $adminUiBundle = $bundles['EzPlatformAdminUiBundle'];
        $adminUiDir = preg_replace('/bundle$/', '', strtolower($adminUiBundle->getName()));

        return $bundlesDir . $adminUiDir . self::TARGET_FILE;
    }
}
