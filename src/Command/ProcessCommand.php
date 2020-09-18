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
class ProcessCommand extends Command
{
    /**
     * The job builder factory.
     *
     * @var JobBuilderFactory
     */
    private $jobBuilderFactory;

    /**
     * Create a new instance.
     *
     * @param JobBuilderFactory $jobBuilderFactory The job builder factory.
     */
    public function __construct(JobBuilderFactory $jobBuilderFactory)
    {
        parent::__construct();
        $this->jobBuilderFactory = $jobBuilderFactory;
    }

    /**
     * {@inheritDoc}
     */
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun     = $input->getOption('dry-run');
        $jobBuilder = $this->jobBuilderFactory->create($input->getOption('job-config'));
        $batchJob   = new BatchJob(array_map(function (string $jobName) use ($jobBuilder) {
            return $jobBuilder->createJobByName($jobName);
        }, ($jobNames = $input->getArgument('jobs'))
                ? $jobNames
                : $jobBuilder->getJobNames()));

        $batchJob->run($dryRun);

        return 0;
    }
}
