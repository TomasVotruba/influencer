<?php

declare(strict_types=1);

namespace Rector\Influencer\FileInfluencer;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\SmartFileSystem\SmartFileInfo;

final class FrameworkComposerVersionInfluencer
{
    /**
     * @var string[]
     */
    private const REQUIRE_SECTIONS = ['require', 'require-dev'];

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    public function __construct(SymfonyStyle $symfonyStyle)
    {
        $this->symfonyStyle = $symfonyStyle;
    }

    public function updateRequirementsByVendorToVersion(
        SmartFileInfo $composerJsonFileInfo,
        string $targetPackage,
        string $targetVersion
    ): void {
        $composerJsonContent = $composerJsonFileInfo->getContents();
        $composerJson = Json::decode($composerJsonContent, Json::FORCE_ARRAY);
        $originalComposerJson = $composerJson;

        // update composer.json
        $composerJson = $this->processSections($targetPackage, $targetVersion, $composerJson);

        // nothing has changed
        if ($originalComposerJson === $composerJson) {
            return;
        }

        $newComposerJsonFileContent = Json::encode($composerJson, Json::PRETTY);
        FileSystem::write($composerJsonFileInfo->getRealPath(), $newComposerJsonFileContent);

        $this->symfonyStyle->note(sprintf(
            'Composer dependency allowed version "%s" for "%s/*" packages',
            $targetVersion,
            $targetPackage
        ));
    }

    private function processSections(string $targetPackage, string $targetVersion, $composerJson)
    {
        foreach (self::REQUIRE_SECTIONS as $section) {
            if (! isset($composerJson[$section])) {
                continue;
            }

            foreach ($composerJson[$section] as $package => $version) {
                if (! $this->isPackageMatch($package, $targetPackage, $version, $targetVersion)) {
                    continue;
                }

                $composerJson[$section][$package] = '^' . $targetVersion;
            }
        }
        return $composerJson;
    }

    private function isPackageMatch(
        string $currentPackage,
        string $targetPackage,
        string $version,
        string $targetVersion
    ): bool {
        if ($targetPackage === 'symfony') {
            // polyfill packages have different package versionining
            if (Strings::match($currentPackage, '#^symfony\/polyfill#')) {
                return false;
            }
        }

        if ($version === '^' . $targetVersion) {
            return false;
        }

        if (Strings::match($currentPackage, '#^' . $targetPackage . '\/#')) {
            return true;
        }

        if ($currentPackage === $targetPackage) {
            return true;
        }

        return false;
    }
}
