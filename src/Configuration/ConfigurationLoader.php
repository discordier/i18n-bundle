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

namespace CyberSpectrum\I18NBundle\Configuration;

use CyberSpectrum\I18N\Configuration\AbstractConfigurationLoader;
use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder;
use CyberSpectrum\I18N\Configuration\LoaderInterface;
use CyberSpectrum\I18NBundle\Configuration\Loader\YamlLoader;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * This loads a config file.
 */
class ConfigurationLoader extends AbstractConfigurationLoader
{
    /**
     * The definition builder.
     *
     * @var DefinitionBuilder
     */
    protected $definitionBuilder;

    /**
     * The file locator to use.
     *
     * @var FileLocatorInterface
     */
    private $locator;

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
