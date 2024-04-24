<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/** Bundle configuration. */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     *
     * @psalm-suppress MixedMethodCall
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cyberspectrum_i18n');
        $rootNode = $treeBuilder->getRootNode();
        /** @psalm-suppress UndefinedMethod */
        $rootNode
            ->children()
                ->booleanNode('enable_xliff')
                    ->defaultTrue()
                ->end()
                ->booleanNode('enable_memory')
                    ->defaultTrue()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
