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

namespace CyberSpectrum\I18NBundle\Job;

use CyberSpectrum\I18N\Job\JobFactory;
use CyberSpectrum\I18N\Job\JobFactoryFactory;
use CyberSpectrum\I18NBundle\Configuration\ConfigurationLoader;

/**
 * This builds translation jobs.
 */
class JobBuilderFactory
{
    /**
     * The dictionary providers.
     *
     * @var JobFactoryFactory
     */
    private $jobFactoryFactory;

    /**
     * The job configuration factory.
     *
     * @var ConfigurationLoader
     */
    private $configurationFactory;

    /**
     * Create a new instance.
     *
     * @param ConfigurationLoader $configurationFactory The job configuration factory.
     * @param JobFactoryFactory   $jobFactoryFactory    The dictionary builders.
     */
    public function __construct(
        ConfigurationLoader $configurationFactory,
        JobFactoryFactory $jobFactoryFactory
    ) {
        $this->jobFactoryFactory    = $jobFactoryFactory;
        $this->configurationFactory = $configurationFactory;
    }

    /**
     * Process a configuration.
     *
     * @param string $configuration The configuration to process.
     *
     * @return JobFactory
     *
     * @throws \InvalidArgumentException When the type is unknown.
     */
    public function create($configuration): JobFactory
    {
        return $this->jobFactoryFactory->create($this->configurationFactory->load($configuration));
    }
}
