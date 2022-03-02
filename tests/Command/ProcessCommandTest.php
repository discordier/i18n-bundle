<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\Command;

use CyberSpectrum\I18N\Job\TranslationJobInterface;
use CyberSpectrum\I18NBundle\Command\ProcessCommand;
use CyberSpectrum\I18N\Job\JobFactory;
use CyberSpectrum\I18NBundle\Job\JobBuilderFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/** @covers \CyberSpectrum\I18NBundle\Command\ProcessCommand */
final class ProcessCommandTest extends TestCase
{
    public function testConfigure(): void
    {
        $factory = $this
            ->getMockBuilder(JobBuilderFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $factory->expects($this->never())->method('create');

        $command = new ProcessCommand($factory);

        self::assertSame('i18n:process', $command->getName());
        self::assertSame('Process translation conversion jobs', $command->getDescription());
    }

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
            ->onlyMethods(['create'])
            ->getMock();

        $factory
            ->expects($this->once())
            ->method('create')
            ->with('.translation-jobs.yml')
            ->willReturn($jobBuilder);

        $command = new ProcessCommand($factory);

        $command->run(new ArrayInput([]), $output = new BufferedOutput());
        self::assertSame('', $output->fetch());
    }

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
            ->onlyMethods(['create'])
            ->getMock();

        $factory
            ->expects($this->once())
            ->method('create')
            ->with('.translation-jobs.yml')
            ->willReturn($jobBuilder);

        $command = new ProcessCommand($factory);

        $command->run(new ArrayInput(['jobs' => ['job']]), $output = new BufferedOutput());
        self::assertSame('', $output->fetch());
    }
}
