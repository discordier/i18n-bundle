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
 * @copyright  2020 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/i18n-bundle/blob/master/LICENSE MIT
 * @filesource
 */

declare(strict_types = 1);

namespace CyberSpectrum\I18NBundle\Test\Configuration\Loader;

use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder;
use CyberSpectrum\I18NBundle\Configuration\Loader\AbstractFileLoader;
use CyberSpectrum\I18NBundle\Configuration\Loader\YamlLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * This tests the configuration loader.
 *
 * @covers \CyberSpectrum\I18NBundle\Configuration\Loader\AbstractFileLoader
 */
class YamlLoaderTest extends TestCase
{
    /**
     * Test the abstract file loader.
     *
     * @return void
     */
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

        $this->assertSame($config, $loader->getConfiguration());
    }
}
