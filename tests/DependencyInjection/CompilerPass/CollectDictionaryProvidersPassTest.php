<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\DependencyInjection\CompilerPass;

use CyberSpectrum\I18N\Memory\MemoryDictionaryProvider;
use CyberSpectrum\I18NBundle\DependencyInjection\CompilerPass\CollectDictionaryProvidersPass;
use CyberSpectrum\I18N\Dictionary\DictionaryProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/** @covers \CyberSpectrum\I18NBundle\DependencyInjection\CompilerPass\CollectDictionaryProvidersPass */
final class CollectDictionaryProvidersPassTest extends TestCase
{
    public function testCollectsProviders(): void
    {
        $container = new ContainerBuilder();
        $registry  = new Definition(ServiceLocator::class);

        $tagged1 = new Definition(DictionaryProviderInterface::class);
        $tagged1->addTag(CollectDictionaryProvidersPass::TAG_DICTIONARY_PROVIDER, ['provider' => 'provider1']);
        $tagged2 = new Definition(DictionaryProviderInterface::class);
        $tagged2->addTag(CollectDictionaryProvidersPass::TAG_DICTIONARY_PROVIDER, ['provider' => 'provider2']);
        $container->addDefinitions(
            ['cyberspectrum_i18n.providers' => $registry, 'service1' => $tagged1, 'service2' => $tagged2]
        );
        unset($registry, $tagged1, $tagged2);

        $servicePass = new CollectDictionaryProvidersPass();
        $servicePass->process($container);

        $registry  = $container->getDefinition('cyberspectrum_i18n.providers');
        $arguments = $registry->getArguments();

        self::assertCount(1, $arguments);
        self::assertSame(['provider1', 'provider2'], array_keys($arguments[0]));
        self::assertInstanceOf(Reference::class, $arguments[0]['provider1']);
        self::assertSame('service1', (string) $arguments[0]['provider1']);
        self::assertInstanceOf(Reference::class, $arguments[0]['provider2']);
        self::assertSame('service2', (string) $arguments[0]['provider2']);
    }

    public function testThrowsForMultipleProvidersWithSameName(): void
    {
        $container = new ContainerBuilder();
        $registry  = new Definition(ServiceLocator::class);

        $registry->setArguments([['provider-name' => new Definition(DictionaryProviderInterface::class)]]);

        $tagged = new Definition(DictionaryProviderInterface::class);
        $tagged->addTag(CollectDictionaryProvidersPass::TAG_DICTIONARY_PROVIDER, ['provider' => 'provider-name']);
        $container->addDefinitions(
            ['cyberspectrum_i18n.providers' => $registry, 'service' => $tagged]
        );
        unset($registry, $tagged);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Multiple dictionary providers with name "provider-name".');

        $servicePass = new CollectDictionaryProvidersPass();
        $servicePass->process($container);
    }

    public function testDoesNothingWhenNoProviderAvailable(): void
    {
        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->setMethods(['findTaggedServiceIds', 'getDefinition'])
            ->getMock();

        $container->expects($this->once())->method('findTaggedServiceIds')->willReturn([]);
        $container->expects($this->never())->method('getDefinition');

        $servicePass = new CollectDictionaryProvidersPass();
        $servicePass->process($container);
    }

    public function testAutoConfigureProviderName(): void
    {
        $container = new ContainerBuilder();
        $registry  = new Definition(ServiceLocator::class);
        $registry->setPublic(true);

        $tagged = new Definition(MemoryDictionaryProvider::class);
        $tagged->addTag(CollectDictionaryProvidersPass::TAG_DICTIONARY_PROVIDER, ['provider' => null]);
        $tagged->setAutoconfigured(true);
        $container->addDefinitions(['cyberspectrum_i18n.providers' => $registry, 'service1' => $tagged]);
        $container->setParameter('cyberspectrum_i18n.provider_names', []);
        $container->setParameter('kernel.root_dir', sys_get_temp_dir());
        $container
            ->registerForAutoconfiguration(DictionaryProviderInterface::class)
            ->addTag(CollectDictionaryProvidersPass::TAG_DICTIONARY_PROVIDER, ['provider' => null]);
        unset($registry, $tagged);

        $servicePass = new CollectDictionaryProvidersPass();
        $servicePass->process($container);

        $registry  = $container->getDefinition('cyberspectrum_i18n.providers');
        $arguments = $registry->getArguments();

        self::assertCount(1, $arguments);
        self::assertSame(['memory'], array_keys($arguments[0]));
        self::assertInstanceOf(Reference::class, $arguments[0]['memory']);
        self::assertSame('service1', (string) $arguments[0]['memory']);
    }

    public function testThrowsForProvidersWithoutName(): void
    {
        $container = new ContainerBuilder();
        $registry  = new Definition(ServiceLocator::class);

        $tagged = new Definition(DictionaryProviderInterface::class);
        $tagged->addTag(CollectDictionaryProvidersPass::TAG_DICTIONARY_PROVIDER);
        $container->addDefinitions(
            ['cyberspectrum_i18n.providers' => $registry, 'service' => $tagged]
        );
        unset($registry, $tagged);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Tag "cyberspectrum_i18n.dictionary_provider" for service "service" has no provider key.'
        );

        $servicePass = new CollectDictionaryProvidersPass();
        $servicePass->process($container);
    }

    public function testThrowsForProvidersNotFollowingNamingConvention(): void
    {
        $container = new ContainerBuilder();
        $registry  = new Definition(ServiceLocator::class);

        $tagged = new Definition(DictionaryProviderInterface::class);
        $tagged->addTag(CollectDictionaryProvidersPass::TAG_DICTIONARY_PROVIDER, ['provider' => null]);
        $container->addDefinitions(
            ['cyberspectrum_i18n.providers' => $registry, 'service' => $tagged]
        );
        unset($registry, $tagged);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'DictionaryProvider "' . DictionaryProviderInterface::class .
            '" does not follow the naming convention; can not configure automatically.'
        );

        $servicePass = new CollectDictionaryProvidersPass();
        $servicePass->process($container);
    }
}
