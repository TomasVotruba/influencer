<?php

declare(strict_types=1);

namespace Rector\Influencer\Console\Command;

use Rector\Influencer\Configuration\Option;
use Rector\Influencer\Exception\ShouldNotHappenException;
use Rector\Influencer\FileInfluencer\ClearPlatformComposerVersionInfluencer;
use Rector\Influencer\FileInfluencer\FrameworkComposerVersionInfluencer;
use Rector\Influencer\FileInfluencer\RemovePackagesFromComposerInfluencer;
use Rector\Influencer\FileInfluencer\SetUpTearDownVoidFileInfluencer;
use Rector\Influencer\Finder\PhpFileFinder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symplify\PackageBuilder\Console\ShellCode;
use Symplify\SmartFileSystem\SmartFileInfo;

final class InfluenceCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var SetUpTearDownVoidFileInfluencer
     */
    private $setUpTearDownVoidFileInfluencer;

    /**
     * @var FrameworkComposerVersionInfluencer
     */
    private $frameworkComposerVersionInfluencer;

    /**
     * @var ClearPlatformComposerVersionInfluencer
     */
    private $clearPlatformComposerVersionInfluencer;

    /**
     * @var PhpFileFinder
     */
    private $phpFileFinder;

    /**
     * @var RemovePackagesFromComposerInfluencer
     */
    private $removePackagesFromComposerInfluencer;

    public function __construct(
        SymfonyStyle $symfonyStyle,
        PhpFileFinder $phpFileFinder,
        SetUpTearDownVoidFileInfluencer $setUpTearDownVoidFileInfluencer,
        FrameworkComposerVersionInfluencer $frameworkComposerVersionInfluencer,
        ClearPlatformComposerVersionInfluencer $clearPlatformComposerVersionInfluencer,
        RemovePackagesFromComposerInfluencer $removePackagesFromComposerInfluencer
    ) {
        $this->symfonyStyle = $symfonyStyle;
        $this->setUpTearDownVoidFileInfluencer = $setUpTearDownVoidFileInfluencer;
        $this->frameworkComposerVersionInfluencer = $frameworkComposerVersionInfluencer;
        $this->clearPlatformComposerVersionInfluencer = $clearPlatformComposerVersionInfluencer;
        $this->phpFileFinder = $phpFileFinder;
        $this->removePackagesFromComposerInfluencer = $removePackagesFromComposerInfluencer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->addArgument(Option::SOURCE, InputArgument::REQUIRED, 'Application root destination');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = (string) $input->getArgument(Option::SOURCE);
        $phpFiles = $this->phpFileFinder->findInDirectory($directory);

        // 1. add setUp()/tearDown() void types
        foreach ($phpFiles as $fileInfo) {
            $this->setUpTearDownVoidFileInfluencer->influenceFile($fileInfo);
        }

        // 2. bump composer.json symfony/* to ^3.4
        $composerJsonFilePath = $directory . '/composer.json';
        if (! file_exists($composerJsonFilePath)) {
            throw new ShouldNotHappenException(sprintf('File "%s" was not found', $composerJsonFilePath));
        }

        $composerJsonSmartFileInfo = new SmartFileInfo($composerJsonFilePath);

        $this->frameworkComposerVersionInfluencer->updateRequirementsByVendorToVersion(
            $composerJsonSmartFileInfo,
            'symfony',
            '3.4'
        );

        // 3. bump PHP to ^7.1
        $this->frameworkComposerVersionInfluencer->updateRequirementsByVendorToVersion(
            $composerJsonSmartFileInfo,
            'php',
            '7.2'
        );

        // 4. remove config platform
        $this->clearPlatformComposerVersionInfluencer->processComposerJsonFile($composerJsonSmartFileInfo);

        // 5. remove dead packages
        $packagesToRemove = ['willdurand/oauth-server-bundle'];
        $this->removePackagesFromComposerInfluencer->processComposerJsonFile(
            $composerJsonSmartFileInfo,
            $packagesToRemove
        );

        $this->symfonyStyle->success('Done!');

        return ShellCode::SUCCESS;
    }
}
