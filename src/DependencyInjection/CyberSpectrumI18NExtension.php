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

namespace CyberSpectrum\I18NBundle\DependencyInjection;

use CyberSpectrum\I18N\Configuration\DefinitionBuilder\MemoryDictionaryDefinitionBuilder;
use CyberSpectrum\I18N\Dictionary\DictionaryProviderInterface;
use CyberSpectrum\I18N\Xliff\XliffDictionaryDefinitionBuilder;
use CyberSpectrum\I18N\Xliff\XliffDictionaryProvider;
use CyberSpectrum\I18NBundle\DependencyInjection\CompilerPass\CollectDictionaryProvidersPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages the bundle configuration
 */
class CyberSpectrumI18NExtension extends Extension
{
    /**
     * Overrides the name.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return 'cyberspectrum_i18n';
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $builders = [];

        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        if ((false !== $config['enable_memory'])) {
            $loader->load('memory.yml');
            $builders['memory'] = new Reference(MemoryDictionaryDefinitionBuilder::class);
        }
        if ((false !== $config['enable_xliff']) && class_exists(XliffDictionaryProvider::class)) {
            $loader->load('xliff.yml');
            $builders['xliff'] = new Reference(XliffDictionaryDefinitionBuilder::class);
        }

        if ([] !== $builders) {
            $definition = $container->getDefinition('cyberspectrum_i18n.dictionary_definition_builders');
            $definition->setArgument(0, array_merge($definition->getArgument(0), $builders));
        }

        $container
            ->registerForAutoconfiguration(DictionaryProviderInterface::class)
            ->addTag(CollectDictionaryProvidersPass::TAG_DICTIONARY_PROVIDER, ['provider' => null]);
    }
}
