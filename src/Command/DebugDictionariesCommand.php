<?php

declare(strict_types=1);

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

use function array_key_last;
use function assert;

/**
 * This class provides a command to list the dictionaries.
 */
final class DebugDictionariesCommand extends Command
{
    /** The dictionary providers. */
    private IdProvidingServiceLocator $providers;

    /**
     * @param IdProvidingServiceLocator $providers The dictionary locator.
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
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $filterProvider */
        $filterProvider = $input->getArgument('provider');
        /** @var string|null $sourceLanguage */
        $sourceLanguage = $input->getOption('source-language');
        /** @var string|null $targetLanguage */
        $targetLanguage = $input->getOption('target-language');

        $list  = $this->getDictionaryList($filterProvider, $sourceLanguage, $targetLanguage);
        $last  = array_key_last($list);
        $table = new Table($output);
        foreach ($list as $providerName => $dictionaries) {
            $table->addRow([new TableCell($providerName, array('colspan' => 3))]);
            $table->addRow(new TableSeparator());
            foreach ($dictionaries as $information) {
                $table->addRow(
                    [$information->getName(), $information->getSourceLanguage(), $information->getTargetLanguage()]
                );
            }
            if ($providerName !== $last) {
                $table->addRow(new TableSeparator());
            }
        }

        $table->render();

        return 0;
    }

    /**
     * Generate the list.
     *
     * @param string|null $filterProvider The provider to filter.
     * @param string|null $sourceLanguage The source language to filter, if any.
     * @param string|null $targetLanguage The target language to filter, if any.
     *
     * @return array<string, list<DictionaryInformation>>
     */
    protected function getDictionaryList(
        ?string $filterProvider,
        ?string $sourceLanguage,
        ?string $targetLanguage
    ): array {
        $list = [];
        foreach ($this->providers->ids() as $providerName) {
            $dictionaries = [];
            if ('' !== ($filterProvider ?? '') && ($filterProvider !== $providerName)) {
                continue;
            }
            $provider = $this->providers->get($providerName);
            assert($provider instanceof DictionaryProviderInterface);
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
     */
    private function allowedLanguage(string $language, ?string $filter): bool
    {
        return (null === $filter) || ($language === $filter);
    }
}
