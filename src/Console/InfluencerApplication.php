<?php

declare(strict_types=1);

namespace Rector\Influencer\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

final class InfluencerApplication extends Application
{
    /**
     * @param Command[] $commands
     */
    public function __construct(array $commands = [])
    {
        parent::__construct('Influencer');

        $this->addCommands($commands);
    }
}
