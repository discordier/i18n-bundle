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

namespace CyberSpectrum\I18NBundle\Test\Configuration\Loader;

use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * This tests the configuration loader.
 *
 * @covers \CyberSpectrum\I18NBundle\Configuration\Loader\AbstractFileLoader
 */
class AbstractFileLoaderTest extends TestCase
{
    /**
     * Test the abstract file loader.
     *
     * @return void
     */
    public function testLoading(): void
    {
        $definitionBuilder = $this
            ->getMockBuilder(DefinitionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            'dictionaries' => [
                'test-dictionary' => [
                    'type' => 'dummy'
                ],
            ],
            'jobs' => [
                'test-job' => [
                    'type' => 'dummy'
                ],
            ],
        ];

        $config = new Configuration();
        $loader = new DummyLoader(
            $config,
            $this->getMockForAbstractClass(FileLocatorInterface::class),
            $definitionBuilder
        );

        $definitionBuilder
            ->expects($this->once())
            ->method('buildDictionary')
            ->with($config, ['name' => 'test-dictionary', 'type' => 'dummy']);

        $definitionBuilder
            ->expects($this->once())
            ->method('buildJob')
            ->with($config, ['name' => 'test-job', 'type' => 'dummy']);

        $loader->load($data);

        $this->assertSame($config, $loader->getConfiguration());
    }

    /**
     * Test exceptions are bubbled up.
     *
     * @return void
     */
    public function testThrowsWhenDictionaryServiceNotFound(): void
    {
        $config = new Configuration();

        $definitionBuilder = $this
            ->getMockBuilder(DefinitionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $definitionBuilder
            ->expects($this->once())
            ->method('buildDictionary')
            ->with($config, ['name' => 'test-dictionary', 'type' => 'dummy'])
            ->willThrowException(new ServiceNotFoundException('dummy'));

        $config = new Configuration();
        $loader = new DummyLoader(
            $config,
            $this->getMockForAbstractClass(FileLocatorInterface::class),
            $definitionBuilder
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid configuration in /dummy/config');

        $loader->load([
            'dictionaries' => [
                'test-dictionary' => ['type' => 'dummy']
            ]
        ]);
    }

    /**
     * Test exceptions are bubbled up.
     *
     * @return void
     */
    public function testThrowsWhenJobServiceNotFound(): void
    {
        $config = new Configuration();

        $definitionBuilder = $this
            ->getMockBuilder(DefinitionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $definitionBuilder
            ->expects($this->once())
            ->method('buildJob')
            ->with($config, ['name' => 'test-job', 'type' => 'dummy'])
            ->willThrowException(new ServiceNotFoundException('dummy'));

        $config = new Configuration();
        $loader = new DummyLoader(
            $config,
            $this->getMockForAbstractClass(FileLocatorInterface::class),
            $definitionBuilder
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid configuration in /dummy/config');

        $loader->load([
            'jobs' => [
                'test-job' => ['type' => 'dummy']
            ]
        ]);
    }
}
