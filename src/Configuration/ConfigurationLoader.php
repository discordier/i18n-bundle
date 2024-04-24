<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Configuration;

use CyberSpectrum\I18N\Configuration\AbstractConfigurationLoader;
use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder;
use CyberSpectrum\I18N\Configuration\LoaderInterface;
use CyberSpectrum\I18NBundle\Configuration\Loader\YamlLoader;
use Symfony\Component\Config\FileLocatorInterface;

/** This loads a config file. */
final class ConfigurationLoader extends AbstractConfigurationLoader
{
    /** The definition builder. */
    protected DefinitionBuilder $definitionBuilder;

    /** The file locator to use. */
    private FileLocatorInterface $locator;

    /**
     * Create a new instance.
     *
     * @param FileLocatorInterface $locator            The file locator instance.
     * @param DefinitionBuilder    $definitionBuilder  The definition builder.
     */
    public function __construct(FileLocatorInterface $locator, DefinitionBuilder $definitionBuilder)
    {
        $this->locator           = $locator;
        $this->definitionBuilder = $definitionBuilder;
    }

    /**
     * {@inheritDoc}
     */
    protected function getLoader($source, Configuration $configuration): LoaderInterface
    {
        return new YamlLoader($configuration, $this->locator, $this->definitionBuilder);
    }
}
