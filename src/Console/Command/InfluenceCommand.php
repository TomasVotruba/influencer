<?php declare(strict_types=1);

namespace Rector\Influencer\Console\Command;

use Nette\Utils\Json;
use Rector\Influencer\Configuration\Option;
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

    public function __construct(
        SymfonyStyle $symfonyStyle,
        SetUpTearDownVoidFileInfluencer $setUpTearDownVoidFileInfluencer,
        FrameworkComposerVersionInfluencer $frameworkComposerVersionInfluencer
    ) {
        $this->symfonyStyle = $symfonyStyle;
        $this->setUpTearDownVoidFileInfluencer = $setUpTearDownVoidFileInfluencer;
        $this->frameworkComposerVersionInfluencer = $frameworkComposerVersionInfluencer;

        parent::__construct();
    }

    protected function configure()
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

        // 2. bump composer.json symfony to 3.4
        $composerJsonFilePath = $directory . '/composer.json';
        $this->frameworkComposerVersionInfluencer->updateRequirementsByVendorToVersion($composerJsonFilePath, 'symfony', '3.4');

        $this->frameworkComposerVersionInfluencer->updateRequirementsByVendorToVersion($composerJsonFilePath,
            'php', '7.1');

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
