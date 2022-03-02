<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Command;

use CyberSpectrum\I18N\Job\BatchJob;
use CyberSpectrum\I18NBundle\Job\JobBuilderFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This class provides a command to process jobs.
 */
final class ProcessCommand extends Command
{
    /** The job builder factory. */
    private JobBuilderFactory $jobBuilderFactory;

    /**
     * @param JobBuilderFactory $jobBuilderFactory The job builder factory.
     */
    public function __construct(JobBuilderFactory $jobBuilderFactory)
    {
        parent::__construct();
        $this->jobBuilderFactory = $jobBuilderFactory;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName('i18n:process');
        $this->setDescription('Process translation conversion jobs');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'If set, no updates will be performed.');
        $this->addOption('job-config', 'c', InputOption::VALUE_REQUIRED, 'The config file', '.translation-jobs.yml');
        $this->addArgument(
            'jobs',
            (InputArgument::OPTIONAL | InputArgument::IS_ARRAY),
            'The name of the jobs to process'
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var list<string>|null $jobNames */
        $jobNames   = $input->getArgument('jobs') ?: null;
        $dryRun     = (bool) $input->getOption('dry-run');
        $jobBuilder = $this->jobBuilderFactory->create((string) $input->getOption('job-config'));

        $jobs = [];
        foreach ($jobNames ?? $jobBuilder->getJobNames() as $jobName) {
            $jobs[$jobName] = $jobBuilder->createJobByName($jobName);
        }
        $batchJob = new BatchJob($jobs);

        $batchJob->run($dryRun);

        return 0;
    }
}
