<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\Command;

use ArrayIterator;
use Closure;
use CyberSpectrum\I18N\Dictionary\DictionaryInformation;
use CyberSpectrum\I18N\Dictionary\DictionaryProviderInterface;
use CyberSpectrum\I18NBundle\Command\DebugDictionariesCommand;
use CyberSpectrum\I18N\DependencyInjection\IdProvidingServiceLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/** @covers \CyberSpectrum\I18NBundle\Command\DebugDictionariesCommand */
final class DebugDictionariesCommandTest extends TestCase
{
    public function testConfigure(): void
    {
        $providers = new IdProvidingServiceLocator([]);

        $command = new DebugDictionariesCommand($providers);

        self::assertSame('debug:i18n-dictionaries', $command->getName());
        self::assertSame('List dictionaries', $command->getDescription());
        $definition = $command->getDefinition();
        self::assertTrue($definition->hasArgument('provider'));
        self::assertTrue($definition->hasOption('source-language'));
        self::assertTrue($definition->hasOption('target-language'));
    }

    public function testDefaultExecution(): void
    {
        $provider1 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider1->expects($this->once())->method('getAvailableDictionaries')->willReturn(new ArrayIterator([
            new DictionaryInformation('foo11', 'en', 'de'),
            new DictionaryInformation('foo12', 'en', 'de'),
        ]));
        $provider2 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider2->expects($this->once())->method('getAvailableDictionaries')->willReturn(new ArrayIterator([
        ]));
        $provider3 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider3->expects($this->once())->method('getAvailableDictionaries')->willReturn(new ArrayIterator([
            new DictionaryInformation('foo31', 'en', 'de'),
        ]));

        $providers = new IdProvidingServiceLocator([
            'provider1' => Closure::fromCallable(function () use ($provider1) {
                return $provider1;
            }),
            'provider2' => Closure::fromCallable(function () use ($provider2) {
                return $provider2;
            }),
            'provider3' => Closure::fromCallable(function () use ($provider3) {
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
        self::assertSame($expected, $output->fetch());
    }

    public function testWithProviderFilter(): void
    {
        $provider1 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider1->expects($this->once())->method('getAvailableDictionaries')->willReturn(new ArrayIterator([
            new DictionaryInformation('foo11', 'en', 'de'),
            new DictionaryInformation('foo12', 'en', 'de'),
        ]));
        $provider2 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider2->expects($this->never())->method('getAvailableDictionaries');
        $provider3 = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider3->expects($this->never())->method('getAvailableDictionaries');

        $providers = new IdProvidingServiceLocator([
            'provider1' => Closure::fromCallable(function () use ($provider1) {
                return $provider1;
            }),
            'provider2' => Closure::fromCallable(function () use ($provider2) {
                return $provider2;
            }),
            'provider3' => Closure::fromCallable(function () use ($provider3) {
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
        self::assertSame($expected, $output->fetch());
    }

    public function testWithSourceLanguageFilter(): void
    {
        $provider = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider->expects($this->once())->method('getAvailableDictionaries')->willReturn(new ArrayIterator([
            new DictionaryInformation('foo11', 'en', 'de'),
            new DictionaryInformation('foo12', 'de', 'fr'),
        ]));

        $providers = new IdProvidingServiceLocator([
            'provider1' => Closure::fromCallable(function () use ($provider) {
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
        self::assertSame($expected, $output->fetch());
    }

    public function testWithTargetLanguageFilter(): void
    {
        $provider = $this->getMockForAbstractClass(DictionaryProviderInterface::class);
        $provider->expects($this->once())->method('getAvailableDictionaries')->willReturn(new ArrayIterator([
            new DictionaryInformation('foo11', 'en', 'de'),
            new DictionaryInformation('foo12', 'de', 'fr'),
        ]));

        $providers = new IdProvidingServiceLocator([
            'provider1' => Closure::fromCallable(function () use ($provider) {
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
        self::assertSame($expected, $output->fetch());
    }
}
