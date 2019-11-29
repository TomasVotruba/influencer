<?php

declare(strict_types=1);

namespace Rector\Influencer\FileInfluencer;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\SmartFileSystem\SmartFileInfo;

final class ClearPlatformComposerVersionInfluencer
{
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    public function __construct(SymfonyStyle $symfonyStyle)
    {
        $this->symfonyStyle = $symfonyStyle;
    }

    public function processComposerJsonFile(SmartFileInfo $composerJsonFileInfo): void
    {
        $composerJsonContent = $composerJsonFileInfo->getContents();
        $composerJson = Json::decode($composerJsonContent, Json::FORCE_ARRAY);
        $originalComposerJson = $composerJson;

        if (! isset($originalComposerJson['config']['platform'])) {
            return;
        }

        unset($composerJson['config']['platform']);
        if (count($composerJson['config']) === 0) {
            unset($composerJson['config']);
        }

        $newComposerJsonFileContent = Json::encode($composerJson, Json::PRETTY);
        FileSystem::write($composerJsonFileInfo->getRealPath(), $newComposerJsonFileContent);

        $this->symfonyStyle->note('Platform config wa removed from "composer.json"');
    }
}
