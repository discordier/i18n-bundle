<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\DependencyInjection;

use CyberSpectrum\I18N\Configuration\DefinitionBuilder\MemoryDictionaryDefinitionBuilder;
use CyberSpectrum\I18N\Dictionary\DictionaryProviderInterface;
use CyberSpectrum\I18N\Xliff\XliffDictionaryDefinitionBuilder;
use CyberSpectrum\I18N\Xliff\XliffDictionaryProvider;
use CyberSpectrum\I18NBundle\DependencyInjection\CompilerPass\CollectDictionaryProvidersPass;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/** This is the class that loads and manages the bundle configuration */
final class CyberSpectrumI18NExtension extends Extension
{
    /** Overrides the name. */
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
            $definition->setArgument(0, array_merge((array) $definition->getArgument(0), $builders));
        }

        $container
            ->registerForAutoconfiguration(DictionaryProviderInterface::class)
            ->addTag(CollectDictionaryProvidersPass::TAG_DICTIONARY_PROVIDER, ['provider' => null]);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }
}
