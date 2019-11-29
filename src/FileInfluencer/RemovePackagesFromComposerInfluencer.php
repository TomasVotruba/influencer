<?php

declare(strict_types=1);

namespace Rector\Influencer\FileInfluencer;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Rector\Influencer\ValueObject\ComposerJsonSection;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\SmartFileSystem\SmartFileInfo;

final class RemovePackagesFromComposerInfluencer
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
     * @param string[] $packagesToRemove
     */
    public function processComposerJsonFile(SmartFileInfo $composerJsonFileInfo, array $packagesToRemove): void
    {
        $composerJsonContent = $composerJsonFileInfo->getContents();
        $composerJson = Json::decode($composerJsonContent, Json::FORCE_ARRAY);
        $originalComposerJson = $composerJson;

        $removedPackages = [];
        foreach (ComposerJsonSection::REQUIRES as $section) {
            if (! isset($composerJson[$section])) {
                continue;
            }

            foreach (array_keys($composerJson[$section]) as $package) {
                if (! in_array($package, $packagesToRemove, true)) {
                    continue;
                }

                unset($composerJson[$section][$package]);
                $removedPackages[] = $package;
            }
        }

        // nothing has changed
        if ($originalComposerJson === $composerJson) {
            return;
        }

        $newComposerJsonFileContent = Json::encode($composerJson, Json::PRETTY);
        FileSystem::write($composerJsonFileInfo->getRealPath(), $newComposerJsonFileContent);

        $this->symfonyStyle->note(sprintf(
            'Packages "%s" were removed from "composer.json"', implode('", ', $removedPackages)
        ));
    }
}
