<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\Configuration;

use CyberSpectrum\I18N\Configuration\DefinitionBuilder;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder\BatchJobDefinitionBuilder;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder\CompoundDictionaryDefinitionBuilder;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder\CopyJobDefinitionBuilder;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder\MemoryDictionaryDefinitionBuilder;
use CyberSpectrum\I18N\Xliff\XliffDictionaryDefinitionBuilder;
use CyberSpectrum\I18NBundle\Configuration\ConfigurationLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @covers \CyberSpectrum\I18NBundle\Configuration\ConfigurationLoader
 * @covers \CyberSpectrum\I18NBundle\Configuration\Loader\YamlLoader
 */
final class ConfigurationLoaderTest extends TestCase
{
    /**
     * Data provider for configuration files.
     *
     * @return array
     */
    public function configProvider(): array
    {
        return [
            'simple'           => [
                'expected'     => [
                    'dictionaries' => [],
                    'jobNames' => [],
                ],
                'fixture'      => __DIR__ . '/Fixtures/simple.yml'
            ],
            'complex'          => [
                'expected'     => [
                    'dictionaries' => [
                        'contao_all',
                        'combined-content',
                        'xliff-out',
                    ],
                    'jobNames' => [
                        'export-en-de',
                        'export-en-fr',
                        'export-all',
                        'all',
                    ],
                ],
                'fixture'      => __DIR__ . '/Fixtures/complex.yml'
            ],
        ];
    }

    /**
     * Test loading.
     *
     * @dataProvider configProvider
     */
    public function testLoad(array $expected, string $fixture): void
    {
        $definitionBuilder = new DefinitionBuilder(
            new ServiceLocator([
                'memory' => function () {
                    return new MemoryDictionaryDefinitionBuilder();
                },
                'compound' => function () use (&$definitionBuilder) {
                    return new CompoundDictionaryDefinitionBuilder($definitionBuilder);
                },
                'xliff' => function () {
                    return new XliffDictionaryDefinitionBuilder();
                },
            ]),
            new ServiceLocator([
                'copy' => function () {
                    return new CopyJobDefinitionBuilder();
                },
                'batch' => function () use (&$definitionBuilder) {
                    return new BatchJobDefinitionBuilder($definitionBuilder);
                },
            ])
        );

        $locator = new FileLocator(__DIR__ . '/Fixtures/' . $fixture);
        $factory = new ConfigurationLoader($locator, $definitionBuilder);
        $config = $factory->load($fixture);

        self::assertSame($expected['jobNames'], $config->getJobNames());
        self::assertSame($expected['dictionaries'], $config->getDictionaryNames());
    }

    public function testUnsupportedFileThrows(): void
    {
        $locator = new FileLocator($fixture = __DIR__ . '/Fixtures/empty.xml');
        $factory = new ConfigurationLoader(
            $locator,
            new DefinitionBuilder(new ServiceLocator([]), new ServiceLocator([]))
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported configuration.');

        $factory->load($fixture);
    }

    public function testEmptyFileThrows(): void
    {
        $locator = new FileLocator($fixture = __DIR__ . '/Fixtures/empty.yml');
        $factory = new ConfigurationLoader(
            $locator,
            new DefinitionBuilder(new ServiceLocator([]), new ServiceLocator([]))
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The config file "' . $fixture . '" is not valid. It should contain an array. Check your YAML syntax.'
        );

        $factory->load($fixture);
    }
}
