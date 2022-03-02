<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\Configuration\Loader;

use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/** @covers \CyberSpectrum\I18NBundle\Configuration\Loader\AbstractFileLoader */
final class AbstractFileLoaderTest extends TestCase
{
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

        self::assertSame($config, $loader->getConfiguration());
    }

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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid configuration in /dummy/config');

        $loader->load([
            'dictionaries' => [
                'test-dictionary' => ['type' => 'dummy']
            ]
        ]);
    }

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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid configuration in /dummy/config');

        $loader->load([
            'jobs' => [
                'test-job' => ['type' => 'dummy']
            ]
        ]);
    }
}
