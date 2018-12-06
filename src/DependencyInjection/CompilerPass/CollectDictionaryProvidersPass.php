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

namespace CyberSpectrum\I18NBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This pass adds tagged services to the various factories.
 */
class CollectDictionaryProvidersPass implements CompilerPassInterface
{
    /**
     * The tag name to use for attribute factories.
     */
    public const TAG_DICTIONARY_PROVIDER = 'cyberspectrum_i18n.dictionary_provider';

    /**
     * Collect all tagged dictionary providers.
     *
     * @param ContainerBuilder $container The container builder.
     *
     * @return void
     *
     * @throws \RuntimeException When a tag has no provider name or multiple services have been registered.
     */
    public function process(ContainerBuilder $container): void
    {
        if ([] === $services = $container->findTaggedServiceIds(self::TAG_DICTIONARY_PROVIDER)) {
            return;
        }

        $providerRegistry = $container->getDefinition('cyberspectrum_i18n.providers');
        $arguments        = $providerRegistry->getArguments();
        if (0 === \count($arguments)) {
            $arguments[] = [];
        }
        foreach ($services as $serviceId => $tags) {
            foreach ($tags as $tag) {
                if (!array_key_exists('provider', $tag)) {
                    throw new \RuntimeException(sprintf(
                        'Tag "%1$s" for service "%2$s" has no provider key.',
                        self::TAG_DICTIONARY_PROVIDER,
                        $serviceId
                    ));
                }
                if (null === $providerName = $tag['provider']) {
                    $providerName = $this->getProviderAlias($container->getDefinition($serviceId)->getClass());
                }

                if (isset($arguments[0][$providerName])) {
                    throw new \RuntimeException('Multiple dictionary providers with name "' . $providerName . '".');
                }
                $arguments[0][$providerName] = new Reference($serviceId);
            }
        }

        $providerRegistry->setArguments($arguments);
        $providers = array_keys($arguments[0]);
        asort($providers);
        $container->setParameter('cyberspectrum_i18n.provider_names', $providers);
    }

    /**
     * Returns the default alias for a provider class.
     *
     * This convention is to remove the "DictionaryProvider" postfix from the class name and then lowercase and
     * underscore the result.
     *
     * So:
     *     AcmeHelloDictionaryProvider
     * becomes
     *     acme_hello
     *
     * @param string $className The class name to generate the alias for.
     *
     * @return string The alias
     *
     * @throws \RuntimeException When the provider class name does not follow conventions.
     */
    public function getProviderAlias(string $className): string
    {
        if ('DictionaryProvider' !== substr($className, -18)) {
            throw new \RuntimeException(
                'DictionaryProvider "' . $className .
                '" does not follow the naming convention; can not configure automatically.'
            );
        }
        $classBaseName = substr(strrchr($className, '\\'), 1, -18);

        return ContainerBuilder::underscore($classBaseName);
    }
}
