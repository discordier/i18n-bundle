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

namespace CyberSpectrum\I18NBundle\Configuration\Loader;

use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder;
use CyberSpectrum\I18N\Configuration\LoaderInterface;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * This is loads job configuration files.
 */
abstract class AbstractFileLoader extends FileLoader implements LoaderInterface
{
    /**
     * The configuration being loaded.
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * The services for building definitions.
     *
     * @var DefinitionBuilder
     */
    private $definitionBuilder;

    /**
     * Create a new instance.
     *
     * @param Configuration        $configuration     The configuration to load.
     * @param FileLocatorInterface $locator           The file locator.
     * @param DefinitionBuilder    $definitionBuilder The definition builder.
     */
    public function __construct(
        Configuration $configuration,
        FileLocatorInterface $locator,
        DefinitionBuilder $definitionBuilder
    ) {
        $this->configuration = $configuration;

        parent::__construct($locator);
        $this->definitionBuilder = $definitionBuilder;
    }

    /**
     * Parse the configuration definitions.
     *
     * @param array  $definitions The definitions to parse.
     * @param string $path        The configuration path.
     *
     * @return void
     *
     * @throws \RuntimeException When the config is invalid.
     */
    protected function parseDefinitions(array $definitions, string $path): void
    {
        try {
            if (isset($definitions['dictionaries'])) {
                foreach ($definitions['dictionaries'] as $name => $definition) {
                    if (isset($definition['name'])) {
                        $definition['dictionary'] = $definition['name'];
                    }
                    $definition['name'] = $name;
                    try {
                        $this->configuration->setDictionary(
                            $this->definitionBuilder->buildDictionary($this->configuration, $definition)
                        );
                    } catch (ServiceNotFoundException $exception) {
                        throw new \RuntimeException('Unknown dictionary type ' . $definition['type'], 0, $exception);
                    }
                }
            }

            if (isset($definitions['jobs'])) {
                foreach ($definitions['jobs'] as $name => $definition) {
                    $definition['name'] = $name;
                    try {
                        $this->configuration->setJob(
                            $this->definitionBuilder->buildJob($this->configuration, $definition)
                        );
                    } catch (ServiceNotFoundException $exception) {
                        throw new \RuntimeException('Unknown job type ' . $definition['type']);
                    }
                }
            }
        } catch (\Throwable $previous) {
            throw new \RuntimeException('Invalid configuration in ' . $path, 0, $previous);
        }
    }

    /**
     * Obtain the configuration.
     *
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }
}
