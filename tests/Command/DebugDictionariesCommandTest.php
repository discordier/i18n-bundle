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

use CyberSpectrum\I18N\Dictionary\DictionaryInformation;
use CyberSpectrum\I18N\Dictionary\DictionaryProviderInterface;
use CyberSpectrum\I18NBundle\Command\DebugDictionariesCommand;
use CyberSpectrum\I18N\DependencyInjection\IdProvidingServiceLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * This tests the debug command.
 *
 * @covers \CyberSpectrum\I18NBundle\Command\DebugDictionariesCommand
 */
class DebugDictionariesCommandTest extends TestCase
{
    /**
     * Test the default execution.
     *
     * @return void
     */
    public function testConfigure(): void
    {
        $providers = new IdProvidingServiceLocator([]);

        $command = new DebugDictionariesCommand($providers);

        $this->assertSame('debug:i18n-dictionaries', $command->getName());
        $this->assertSame('List dictionaries', $command->getDescription());
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('provider'));
        $this->assertTrue($definition->hasOption('source-language'));
        $this->assertTrue($definition->hasOption('target-language'));
    }

    /**
     * Test the default execution.
     *
     * @return void
     */
    public function testDefaultExecution(): void
    {
        $provider1 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider1->expects($this->once())->method('getAvailableDictionaries')->willReturn(new \ArrayIterator([
            new DictionaryInformation('foo11', 'en', 'de'),
            new DictionaryInformation('foo12', 'en', 'de'),
        ]));
        $provider2 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider2->expects($this->once())->method('getAvailableDictionaries')->willReturn(new \ArrayIterator([
        ]));
        $provider3 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider3->expects($this->once())->method('getAvailableDictionaries')->willReturn(new \ArrayIterator([
            new DictionaryInformation('foo31', 'en', 'de'),
        ]));

        $providers = new IdProvidingServiceLocator([
            'provider1' => \Closure::fromCallable(function () use ($provider1) {
                return $provider1;
            }),
            'provider2' => \Closure::fromCallable(function () use ($provider2) {
                return $provider2;
            }),
            'provider3' => \Closure::fromCallable(function () use ($provider3) {
                return $provider3;
            }),
        ]);

        $command = new DebugDictionariesCommand($providers);

        $expected = <<<EOF
+-------+-----+-----+
| provider1         |
+-------+-----+-----+
| foo11 | en  | de  |
| foo12 | en  | de  |
+-------+-----+-----+
| provider3         |
+-------+-----+-----+
| foo31 | en  | de  |
+-------+-----+-----+

EOF;
        $command->run(new ArrayInput([]), $output = new BufferedOutput());
        $this->assertSame($expected, $output->fetch());
    }

    /**
     * Test the provider filter.
     *
     * @return void
     */
    public function testWithProviderFilter(): void
    {
        $provider1 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider1->expects($this->once())->method('getAvailableDictionaries')->willReturn(new \ArrayIterator([
            new DictionaryInformation('foo11', 'en', 'de'),
            new DictionaryInformation('foo12', 'en', 'de'),
        ]));
        $provider2 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider2->expects($this->never())->method('getAvailableDictionaries');
        $provider3 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider3->expects($this->never())->method('getAvailableDictionaries');

        $providers = new IdProvidingServiceLocator([
            'provider1' => \Closure::fromCallable(function () use ($provider1) {
                return $provider1;
            }),
            'provider2' => \Closure::fromCallable(function () use ($provider2) {
                return $provider2;
            }),
            'provider3' => \Closure::fromCallable(function () use ($provider3) {
                return $provider3;
            }),
        ]);

        $command = new DebugDictionariesCommand($providers);

        $expected = <<<EOF
+-------+-----+-----+
| provider1         |
+-------+-----+-----+
| foo11 | en  | de  |
| foo12 | en  | de  |
+-------+-----+-----+

EOF;
        $command->run(new ArrayInput(['provider' => 'provider1']), $output = new BufferedOutput());
        $this->assertSame($expected, $output->fetch());
    }

    /**
     * Test the source language filter.
     *
     * @return void
     */
    public function testWithSourceLanguageFilter(): void
    {
        $provider = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider->expects($this->once())->method('getAvailableDictionaries')->willReturn(new \ArrayIterator([
            new DictionaryInformation('foo11', 'en', 'de'),
            new DictionaryInformation('foo12', 'de', 'fr'),
        ]));

        $providers = new IdProvidingServiceLocator([
            'provider1' => \Closure::fromCallable(function () use ($provider) {
                return $provider;
            }),
        ]);

        $command = new DebugDictionariesCommand($providers);

        $expected = <<<EOF
+-------+-----+-----+
| provider1         |
+-------+-----+-----+
| foo11 | en  | de  |
+-------+-----+-----+

EOF;
        $command->run(new ArrayInput(['--source-language' => 'en']), $output = new BufferedOutput());
        $this->assertSame($expected, $output->fetch());
    }

    /**
     * Test the source language filter.
     *
     * @return void
     */
    public function testWithTargetLanguageFilter(): void
    {
        $provider = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider->expects($this->once())->method('getAvailableDictionaries')->willReturn(new \ArrayIterator([
            new DictionaryInformation('foo11', 'en', 'de'),
            new DictionaryInformation('foo12', 'de', 'fr'),
        ]));

        $providers = new IdProvidingServiceLocator([
            'provider1' => \Closure::fromCallable(function () use ($provider) {
                return $provider;
            }),
        ]);

        $command = new DebugDictionariesCommand($providers);

        $expected = <<<EOF
+-------+-----+-----+
| provider1         |
+-------+-----+-----+
| foo11 | en  | de  |
+-------+-----+-----+

EOF;
        $command->run(new ArrayInput(['--target-language' => 'de']), $output = new BufferedOutput());
        $this->assertSame($expected, $output->fetch());
    }
}
