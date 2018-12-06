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

namespace CyberSpectrum\I18NBundle\Test\Job;

use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Job\JobFactory;
use CyberSpectrum\I18N\Job\JobFactoryFactory;
use CyberSpectrum\I18NBundle\Configuration\ConfigurationLoader;
use CyberSpectrum\I18N\Configuration\Definition\Definition;
use CyberSpectrum\I18NBundle\Job\JobBuilderFactory;
use PHPUnit\Framework\TestCase;

/**
 * This tests the job builder factory.
 *
 * @covers \CyberSpectrum\I18NBundle\Job\JobBuilderFactory
 */
class JobBuilderFactoryTest extends TestCase
{
    /**
     * Test the factory.
     *
     * @return void
     */
    public function testCreate(): void
    {
        $configurationFactory = $this
            ->getMockBuilder(ConfigurationLoader::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $configurationFactory
            ->expects($this->once())
            ->method('load')
            ->with('test')
            ->willReturn($configuration = new Configuration());

        $jobFactoryFactory = $this
            ->getMockBuilder(JobFactoryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $jobFactory = $this->getMockBuilder(JobFactory::class)->disableOriginalConstructor()->getMock();
        $jobFactoryFactory
            ->expects($this->once())
            ->method('create')
            ->with($configuration)
            ->willReturn($jobFactory);

        $factory = new JobBuilderFactory($configurationFactory, $jobFactoryFactory);

        $configuration->setJob(new Definition('job'));

        $this->assertSame($jobFactory, $factory->create('test'));
    }
}
