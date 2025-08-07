<?php

declare(strict_types=1);

namespace App\Example\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(self::NAME, self::DESCRIPTION)]
final class ExampleCommand extends Command
{
    public const string NAME = 'app:example';

    public const string DESCRIPTION = 'An example command for demonstration purposes.';

    public function __construct()
    {
        parent::__construct(self::NAME);
        $this->setDescription(self::DESCRIPTION);
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }
}
