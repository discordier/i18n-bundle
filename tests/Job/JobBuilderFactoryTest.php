<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\Job;

use CyberSpectrum\I18N\Configuration\AbstractConfigurationLoader;
use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Job\JobFactory;
use CyberSpectrum\I18N\Job\JobFactoryFactory;
use CyberSpectrum\I18N\Configuration\Definition\Definition;
use CyberSpectrum\I18NBundle\Job\JobBuilderFactory;
use PHPUnit\Framework\TestCase;

/** @covers \CyberSpectrum\I18NBundle\Job\JobBuilderFactory */
final class JobBuilderFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $configurationFactory = $this
            ->getMockBuilder(AbstractConfigurationLoader::class)
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $configurationFactory
            ->expects($this->once())
            ->method('load')
            ->with('test')
            ->willReturn($configuration = new Configuration());

        $jobFactoryFactory = $this
            ->getMockBuilder(JobFactoryFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $jobFactory = $this->getMockBuilder(JobFactory::class)->disableOriginalConstructor()->getMock();
        $jobFactoryFactory
            ->expects(self::once())
            ->method('create')
            ->with($configuration)
            ->willReturn($jobFactory);

        $factory = new JobBuilderFactory($configurationFactory, $jobFactoryFactory);

        $configuration->setJob(new Definition('job'));

        self::assertSame($jobFactory, $factory->create('test'));
    }
}
