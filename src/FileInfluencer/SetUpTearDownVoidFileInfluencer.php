<?php declare(strict_types=1);

namespace Rector\Influencer\FileInfluencer;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Add "void" to setUp(), tearDown() methods
 */
final class SetUpTearDownVoidFileInfluencer
{
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    public function __construct(SymfonyStyle $symfonyStyle)
    {
        $this->symfonyStyle = $symfonyStyle;
    }

    /**
     * @var string
     */
    private const SETUP_TEARDOWN_WITHOUT_VOID = '#(function\s(setUp|tearDown)\(\))\n#i';

    public function influenceFile(SplFileInfo $fileInfo): void
    {
        if (! Strings::match($fileInfo->getContents(), self::SETUP_TEARDOWN_WITHOUT_VOID)) {
            return;
        }

        $newContent = Strings::replace(
            $fileInfo->getContents(),
            self::SETUP_TEARDOWN_WITHOUT_VOID,
            "$1: void\n"
        );

        FileSystem::write($fileInfo->getRealPath(), $newContent);

        $this->symfonyStyle->note(sprintf(
            'File "%s" was added void added for setUp()/tearDown()',
            $fileInfo->getRelativePathname()
        ));
    }
}
