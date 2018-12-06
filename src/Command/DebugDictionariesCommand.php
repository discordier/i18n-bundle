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

use CyberSpectrum\I18N\Dictionary\DictionaryInformation;
use CyberSpectrum\I18N\Dictionary\DictionaryProviderInterface;
use CyberSpectrum\I18N\DependencyInjection\IdProvidingServiceLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This class provides a command to list the dictionaries.
 */
class DebugDictionariesCommand extends Command
{
    /**
     * The dictionary providers.
     *
     * @var IdProvidingServiceLocator
     */
    private $providers;

    /**
     * Create a new instance.
     *
     * @param \CyberSpectrum\I18N\DependencyInjection\IdProvidingServiceLocator $providers The dictionary locator.
     */
    public function __construct(IdProvidingServiceLocator $providers)
    {
        parent::__construct();
        $this->providers = $providers;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName('debug:i18n-dictionaries');
        $this->setDescription('List dictionaries');
        $this->addArgument('provider', InputArgument::OPTIONAL, 'Filter by provider name');
        $this->addOption('source-language', 's', InputOption::VALUE_REQUIRED, 'Filter by source language');
        $this->addOption('target-language', 't', InputOption::VALUE_REQUIRED, 'Filter by target language');
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filterProvider = $input->getArgument('provider');
        $sourceLanguage = $input->getOption('source-language');
        $targetLanguage = $input->getOption('target-language');

        $list  = $this->getDictionaryList($filterProvider, $sourceLanguage, $targetLanguage);
        $keys  = \array_keys($list);
        $last  = $keys[(count($keys) - 1)];
        $table = new Table($output);
        foreach ($list as $providerName => $dictionaries) {
            $table->addRow([new TableCell($providerName, array('colspan' => 3))]);
            $table->addRow(new TableSeparator());
            foreach ($dictionaries as $information) {
                /** @var DictionaryInformation $information */
                $table->addRow(
                    [$information->getName(), $information->getSourceLanguage(), $information->getTargetLanguage()]
                );
            }
            if ($providerName !== $last) {
                $table->addRow(new TableSeparator());
            }
        }

        $table->render();
    }

    /**
     * Generate the list.
     *
     * @param string|null $filterProvider The provider to filter.
     * @param string|null $sourceLanguage The source language to filter, if any.
     * @param string|null $targetLanguage The target language to filter, if any.
     *
     * @return array
     */
    protected function getDictionaryList($filterProvider, $sourceLanguage, $targetLanguage): array
    {
        $list = [];
        foreach ($this->providers->ids() as $providerName) {
            $dictionaries = [];
            if ($filterProvider && ($filterProvider !== $providerName)) {
                continue;
            }
            /** @var DictionaryProviderInterface $provider */
            $provider = $this->providers->get($providerName);
            foreach ($provider->getAvailableDictionaries() as $information) {
                if (!$this->allowedLanguage($information->getSourceLanguage(), $sourceLanguage)) {
                    continue;
                }
                if (!$this->allowedLanguage($information->getTargetLanguage(), $targetLanguage)) {
                    continue;
                }

                $dictionaries[] = $information;
            }
            unset($provider);

            if (!empty($dictionaries)) {
                $list[$providerName] = $dictionaries;
            }
        }

        return $list;
    }

    /**
     * Check if the passed language is allowed.
     *
     * @param string      $language The language to check.
     * @param string|null $filter   The optional filter.
     *
     * @return bool
     */
    private function allowedLanguage(string $language, string $filter = null): bool
    {
        return !$filter || ($language === $filter);
    }
}
