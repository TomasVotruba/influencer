<?php

declare(strict_types=1);

namespace Rector\Influencer\Finder;

use Symfony\Component\Finder\Finder;
use Symplify\SmartFileSystem\Finder\FinderSanitizer;
use Symplify\SmartFileSystem\SmartFileInfo;

final class PhpFileFinder
{
    /**
     * @var FinderSanitizer
     */
    private $finderSanitizer;

    public function __construct(FinderSanitizer $finderSanitizer)
    {
        $this->finderSanitizer = $finderSanitizer;
    }

    /**
     * @return SmartFileInfo[]
     */
    public function findInDirectory(string $directory): array
    {
        $finder = $this->createFinderWithPhpFilesForDirectory($directory);

        return $this->finderSanitizer->sanitize($finder);
    }

    private function createFinderWithPhpFilesForDirectory(string $directory): Finder
    {
        return Finder::create()
            ->files()
            ->name('*.php')
            ->in($directory);
    }
}
