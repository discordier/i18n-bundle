<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Command;

use CyberSpectrum\I18N\DependencyInjection\IdProvidingServiceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This class provides a command to list the providers.
 */
final class DebugProvidersCommand extends Command
{
    /** The dictionary providers. */
    private IdProvidingServiceLocator $providers;

    /** @param IdProvidingServiceLocator $providers The dictionary locator. */
    public function __construct(IdProvidingServiceLocator $providers)
    {
        parent::__construct();
        $this->providers = $providers;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName('debug:i18n-providers');
        $this->setDescription('List dictionary providers');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->providers->ids() as $providerName) {
            $output->writeln($providerName);
        }

        return 0;
    }
}
