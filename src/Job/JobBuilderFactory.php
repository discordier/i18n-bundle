<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Job;

use CyberSpectrum\I18N\Configuration\ConfigurationLoaderInterface;
use CyberSpectrum\I18N\Job\JobFactory;
use CyberSpectrum\I18N\Job\JobFactoryFactory;
use InvalidArgumentException;

/**
 * This builds translation jobs.
 *
 * @final
 */
class JobBuilderFactory
{
    /** The dictionary providers. */
    private JobFactoryFactory $jobFactoryFactory;

    /** The job configuration factory. */
    private ConfigurationLoaderInterface $configurationFactory;

    /**
     * Create a new instance.
     *
     * @param ConfigurationLoaderInterface $configurationFactory The job configuration factory.
     * @param JobFactoryFactory            $jobFactoryFactory    The dictionary builders.
     */
    public function __construct(
        ConfigurationLoaderInterface $configurationFactory,
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
     * @throws InvalidArgumentException When the type is unknown.
     */
    public function create(string $configuration): JobFactory
    {
        return $this->jobFactoryFactory->create($this->configurationFactory->load($configuration));
    }
}
