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

/**
 * This tests the bundle.
 *
 * @covers \CyberSpectrum\I18NBundle\DependencyInjection\CyberSpectrumI18NExtension
 */
class CyberSpectrumI18NExtensionTest extends TestCase
{
    /**
     * Test that the alias is the overridden alias.
     *
     * @return void
     */
    public function testIdIsCorrect(): void
    {
        $extension = new CyberSpectrumI18NExtension();
        $this->assertSame('cyberspectrum_i18n', $extension->getAlias());
    }

    /**
     * Test loading of the configuration.
     *
     * @return void
     */
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('cyberspectrum_i18n.provider_names', []);
        $container->setParameter('kernel.root_dir', sys_get_temp_dir());
        $container->setDefinition('file_locator', new Definition(FileLocator::class));
        $container->setDefinition('logger', new Definition(NullLogger::class));

        $extension = new CyberSpectrumI18NExtension();

        $extension->load([], $container);

        $this->assertLoadedConfigs([
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

            $this->assertTrue(
                class_exists($class) || interface_exists($class),
                'Class ' . $class . ' does not exist for ' . $serviceId
            );
        }

        // Ensure the container compiles.
        $container->compile();
        $this->addToAssertionCount(1);
    }

    /**
     * Test loading of the configuration.
     *
     * @return void
     */
    public function testLoadWithDisabledServices(): void
    {
        $container = new ContainerBuilder();

        $container->setParameter('cyberspectrum_i18n.provider_names', []);
        $container->setParameter('kernel.root_dir', sys_get_temp_dir());
        $container->setDefinition('file_locator', new Definition(FileLocator::class));
        $container->setDefinition('logger', new Definition(NullLogger::class));

        $extension = new CyberSpectrumI18NExtension();

        $extension->load([['enable_xliff' => false, 'enable_memory' => false]], $container);

        $this->assertLoadedConfigs([
            realpath(__DIR__ . '/../../src/Resources/config/services.yml'),
            realpath(__DIR__ . '/../../src/Resources/config/job_related.yml'),
            realpath(__DIR__ . '/../../src/Resources/config/dictionary_related.yml'),
        ], $container);

        // Ensure the container compiles.
        $container->compile();
        $this->addToAssertionCount(1);
    }

    /**
     * Test loading of the configuration.
     *
     * @return void
     */
    public function testAutoConfigurationForDictionaryProvidersIsRegistered(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('cyberspectrum_i18n.provider_names', []);
        $container->setParameter('kernel.root_dir', sys_get_temp_dir());

        $extension = new CyberSpectrumI18NExtension();

        $extension->load([], $container);

        $autoConfigure = $container->getAutoconfiguredInstanceof();
        $this->assertSame([DictionaryProviderInterface::class], array_keys($autoConfigure));
        $this->assertSame(
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
        $this->assertSame($expected, $files);
    }
}
