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

namespace CyberSpectrum\I18NBundle\Test\Command;

use CyberSpectrum\I18N\Job\TranslationJobInterface;
use CyberSpectrum\I18NBundle\Command\ProcessCommand;
use CyberSpectrum\I18N\Job\JobFactory;
use CyberSpectrum\I18NBundle\Job\JobBuilderFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * This tests the debug command.
 *
 * @covers \CyberSpectrum\I18NBundle\Command\ProcessCommand
 */
class ProcessCommandTest extends TestCase
{
    /**
     * Test the default execution.
     *
     * @return void
     */
    public function testConfigure(): void
    {
        $factory = $this
            ->getMockBuilder(JobBuilderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $factory->expects($this->never())->method('create');

        $command = new ProcessCommand($factory);

        $this->assertSame('i18n:process', $command->getName());
        $this->assertSame('Process translation conversion jobs', $command->getDescription());
    }

    /**
     * Test the default execution.
     *
     * @return void
     */
    public function testRunDefault(): void
    {
        $job = $this->getMockBuilder(TranslationJobInterface::class)->getMockForAbstractClass();
        $job->expects($this->once())->method('run')->with(false);
        
        $jobBuilder = $this
            ->getMockBuilder(JobFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $jobBuilder->expects($this->once())->method('getJobNames')->willReturn(['job']);
        $jobBuilder->expects($this->once())->method('createJobByName')->with('job')->willReturn($job);

        $factory = $this
            ->getMockBuilder(JobBuilderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $factory
            ->expects($this->once())
            ->method('create')
            ->with('.translation-jobs.yml')
            ->willReturn($jobBuilder);

        $command = new ProcessCommand($factory);

        $command->run(new ArrayInput([]), $output = new BufferedOutput());
        $this->assertSame('', $output->fetch());
    }

    /**
     * Test the execution of a single job.
     *
     * @return void
     */
    public function testRunSingleJob(): void
    {
        $job = $this->getMockBuilder(TranslationJobInterface::class)->getMockForAbstractClass();
        $job->expects($this->once())->method('run')->with(false);

        $jobBuilder = $this
            ->getMockBuilder(JobFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $jobBuilder->expects($this->never())->method('getJobNames');
        $jobBuilder->expects($this->once())->method('createJobByName')->with('job')->willReturn($job);

        $factory = $this
            ->getMockBuilder(JobBuilderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $factory
            ->expects($this->once())
            ->method('create')
            ->with('.translation-jobs.yml')
            ->willReturn($jobBuilder);

        $command = new ProcessCommand($factory);

        $command->run(new ArrayInput(['jobs' => ['job']]), $output = new BufferedOutput());
        $this->assertSame('', $output->fetch());
    }
}
