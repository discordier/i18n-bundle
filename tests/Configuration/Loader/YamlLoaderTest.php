<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\Configuration\Loader;

use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder;
use CyberSpectrum\I18NBundle\Configuration\Loader\AbstractFileLoader;
use CyberSpectrum\I18NBundle\Configuration\Loader\YamlLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;

/** @covers \CyberSpectrum\I18NBundle\Configuration\Loader\AbstractFileLoader */
final class YamlLoaderTest extends TestCase
{
    public function testLoading(): void
    {
        $config = new Configuration();

        $definitionBuilder = $this
            ->getMockBuilder(DefinitionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $definitionBuilder
            ->expects($this->exactly(2))
            ->method('buildDictionary')
            ->withConsecutive(
                [$config, ['name' => 'xliff-out', 'type' => 'xliff']],
                [$config, ['name' => 'xliff-in', 'type' => 'xliff']]
            );

        $loader = new YamlLoader($config, new FileLocator([__DIR__ . '/../Fixtures']), $definitionBuilder);
        $loader->load('import-parent.yaml');

        self::assertSame($config, $loader->getConfiguration());
    }
}
