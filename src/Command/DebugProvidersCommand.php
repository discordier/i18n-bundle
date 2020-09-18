<?php

/**
 * This file is part of cyberspectrum/i18n-bundle.
 *
 * (c) 2018 CyberSpectrum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    cyberspectrum/i18n-bundle
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2018 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/i18n-bundle/blob/master/LICENSE MIT
 * @filesource
 */

declare(strict_types = 1);

namespace CyberSpectrum\I18NBundle\Command;

use CyberSpectrum\I18N\DependencyInjection\IdProvidingServiceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This class provides a command to list the providers.
 */
class DebugProvidersCommand extends Command
{
    /**
     * The dictionary providers.
     *
     * @var IdProvidingServiceLocator
     */
    private $providers;

    /**
     * Create a new instance.
     *
     * @param \CyberSpectrum\I18N\DependencyInjection\IdProvidingServiceLocator $providers The dictionary locator.
     */
    public function __construct(IdProvidingServiceLocator $providers)
    {
        parent::__construct();
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName('debug:i18n-providers');
        $this->setDescription('List dictionary providers');
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->providers->ids() as $providerName) {
            $output->writeln($providerName);
        }

        return 0;
    }
}
