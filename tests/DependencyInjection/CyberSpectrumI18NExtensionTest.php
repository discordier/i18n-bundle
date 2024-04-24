<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\DependencyInjection;

use CyberSpectrum\I18N\Dictionary\DictionaryProviderInterface;
use CyberSpectrum\I18NBundle\DependencyInjection\CompilerPass\CollectDictionaryProvidersPass;
use CyberSpectrum\I18NBundle\DependencyInjection\CyberSpectrumI18NExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/** @covers \CyberSpectrum\I18NBundle\DependencyInjection\CyberSpectrumI18NExtension */
final class CyberSpectrumI18NExtensionTest extends TestCase
{
    /** Test that the alias is the overridden alias. */
    public function testIdIsCorrect(): void
    {
        $extension = new CyberSpectrumI18NExtension();
        self::assertSame('cyberspectrum_i18n', $extension->getAlias());
    }

    /** Test loading of the configuration. */
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('cyberspectrum_i18n.provider_names', []);
        $container->setParameter('kernel.root_dir', sys_get_temp_dir());
        $container->setDefinition('file_locator', new Definition(FileLocator::class));
        $container->setDefinition('logger', new Definition(NullLogger::class));

        $extension = new CyberSpectrumI18NExtension();

        $extension->load([], $container);

        self::assertLoadedConfigs([
            realpath(__DIR__ . '/../../src/Resources/config/services.yml'),
            realpath(__DIR__ . '/../../src/Resources/config/job_related.yml'),
            realpath(__DIR__ . '/../../src/Resources/config/dictionary_related.yml'),
            realpath(__DIR__ . '/../../src/Resources/config/memory.yml'),
            realpath(__DIR__ . '/../../src/Resources/config/xliff.yml'),
        ], $container);

        // Ensure all definitions are pointing to valid classes or interfaces.
        foreach ($container->getDefinitions() as $serviceId => $definition) {
            if (null === $class = $definition->getClass()) {
                $class = $serviceId;
            }

            self::assertTrue(
                class_exists($class) || interface_exists($class),
                'Class ' . $class . ' does not exist for ' . $serviceId
            );
        }

        // Ensure the container compiles.
        $container->compile();
        $this->addToAssertionCount(1);
    }

    /** Test loading of the configuration. */
    public function testLoadWithDisabledServices(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('cyberspectrum_i18n.provider_names', []);
        $container->setParameter('kernel.root_dir', sys_get_temp_dir());
        $container->setDefinition('file_locator', new Definition(FileLocator::class));
        $container->setDefinition('logger', new Definition(NullLogger::class));

        $extension = new CyberSpectrumI18NExtension();

        $extension->load([['enable_xliff' => false, 'enable_memory' => false]], $container);

        self::assertLoadedConfigs([
            realpath(__DIR__ . '/../../src/Resources/config/services.yml'),
            realpath(__DIR__ . '/../../src/Resources/config/job_related.yml'),
            realpath(__DIR__ . '/../../src/Resources/config/dictionary_related.yml'),
        ], $container);

        // Ensure the container compiles.
        $container->compile();
        $this->addToAssertionCount(1);
    }

    /** Test loading of the configuration. */
    public function testAutoConfigurationForDictionaryProvidersIsRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('cyberspectrum_i18n.provider_names', []);
        $container->setParameter('kernel.root_dir', sys_get_temp_dir());

        $extension = new CyberSpectrumI18NExtension();

        $extension->load([], $container);

        $autoConfigure = $container->getAutoconfiguredInstanceof();
        self::assertSame([DictionaryProviderInterface::class], array_keys($autoConfigure));
        self::assertSame(
            [CollectDictionaryProvidersPass::TAG_DICTIONARY_PROVIDER => [['provider' => null]]],
            $autoConfigure[DictionaryProviderInterface::class]->getTags()
        );
    }

    /**
     * Assert that the loaded resources match.
     *
     * @param array            $expected  The expected file resource names.
     * @param ContainerBuilder $container The container.
     *
     * @return void
     */
    protected function assertLoadedConfigs(array $expected, ContainerBuilder $container): void
    {
        $resources = $container->getResources();
        $files     = [];
        foreach ($resources as $resource) {
            if ($resource instanceof FileResource) {
                $files[] = (string)$resource;
            }
        }
        // Assert we get all files loaded.
        self::assertSame($expected, $files);
    }
}
