<?php

declare(strict_types=1);

namespace Rector\Influencer\Console\Command;

use Rector\Influencer\Configuration\Option;
use Rector\Influencer\FileInfluencer\ClearPlatformComposerVersionInfluencer;
use Rector\Influencer\FileInfluencer\FrameworkComposerVersionInfluencer;
use Rector\Influencer\FileInfluencer\SetUpTearDownVoidFileInfluencer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symplify\PackageBuilder\Console\ShellCode;

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

    public function __construct(
        SymfonyStyle $symfonyStyle,
        SetUpTearDownVoidFileInfluencer $setUpTearDownVoidFileInfluencer,
        FrameworkComposerVersionInfluencer $frameworkComposerVersionInfluencer,
        ClearPlatformComposerVersionInfluencer $clearPlatformComposerVersionInfluencer
    ) {
        $this->symfonyStyle = $symfonyStyle;
        $this->setUpTearDownVoidFileInfluencer = $setUpTearDownVoidFileInfluencer;
        $this->frameworkComposerVersionInfluencer = $frameworkComposerVersionInfluencer;
        $this->clearPlatformComposerVersionInfluencer = $clearPlatformComposerVersionInfluencer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->addArgument(Option::SOURCE, InputArgument::REQUIRED, 'Application root destination');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $input->getArgument(Option::SOURCE);

        $phpFiles = $this->findPhpFilesInDirectory($directory);

        // 1. add setUp()/tearDown() void types
        foreach ($phpFiles as $fileInfo) {
            $this->setUpTearDownVoidFileInfluencer->influenceFile($fileInfo);
        }

        // 2. bump composer.json symfony/* to ^3.4
        $composerJsonFilePath = $directory . '/composer.json';
        if (! file_exists($composerJsonFilePath)) {
            $this->symfonyStyle->error(sprintf('File "%s" was not found', $composerJsonFilePath));
        }

        $this->frameworkComposerVersionInfluencer->updateRequirementsByVendorToVersion($composerJsonFilePath, 'symfony', '3.4');

        // 3. bump PHP to ^7.1
        $this->frameworkComposerVersionInfluencer->updateRequirementsByVendorToVersion(
            $composerJsonFilePath,
            'php',
            '7.2'
        );

        // 4. remove config platform
        $this->clearPlatformComposerVersionInfluencer->processComposerJsonFile($composerJsonFilePath);

        $this->symfonyStyle->success('Done!');

        return ShellCode::SUCCESS;
    }

    /**
     * @return SplFileInfo[]
     */
    private function findPhpFilesInDirectory(string $directory): array
    {
        $finder = Finder::create()
            ->files()
            ->name('*.php')
            ->in($directory);

        return iterator_to_array($finder);
    }
}
