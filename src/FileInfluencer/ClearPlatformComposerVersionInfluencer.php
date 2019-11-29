<?php declare(strict_types=1);

namespace Rector\Influencer\FileInfluencer;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Symfony\Component\Console\Style\SymfonyStyle;

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

    public function processComposerJsonFile(string $composerJsonFilePath): void
    {
        $composerJsonContent = FileSystem::read($composerJsonFilePath);
        $composerJson = Json::decode($composerJsonContent, Json::FORCE_ARRAY);
        $originalComposerJson = $composerJson;

        if (! isset($originalComposerJson['config']['platform'])) {
            return;
        }

        unset($originalComposerJson['config']['platform']);
        if (count($originalComposerJson['config']) === 0) {
            unset($originalComposerJson['config']);
        }

        $newComposerJsonFileContent = Json::encode($composerJson, Json::PRETTY);
        FileSystem::write($composerJsonFilePath, $newComposerJsonFileContent);

        $this->symfonyStyle->note('Platform config wa removed from "composer.json"');
    }
}
