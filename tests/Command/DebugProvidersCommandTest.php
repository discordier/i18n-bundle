<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\Command;

use Closure;
use CyberSpectrum\I18NBundle\Command\DebugProvidersCommand;
use CyberSpectrum\I18N\DependencyInjection\IdProvidingServiceLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/** @covers \CyberSpectrum\I18NBundle\Command\DebugProvidersCommand */
final class DebugProvidersCommandTest extends TestCase
{
    public function testConfigure(): void
    {
        $providers = new IdProvidingServiceLocator([]);

        $command = new DebugProvidersCommand($providers);

        self::assertSame('debug:i18n-providers', $command->getName());
        self::assertSame('List dictionary providers', $command->getDescription());
    }

    public function testDefaultExecution(): void
    {
        $providers = new IdProvidingServiceLocator([
            'provider1' => Closure::fromCallable(function () {
            }),
            'provider2' => Closure::fromCallable(function () {
            }),
            'provider3' => Closure::fromCallable(function () {
            }),
        ]);

        $command = new DebugProvidersCommand($providers);

        $expected = <<<EOF
provider1
provider2
provider3

EOF;

        $command->run(new ArrayInput([]), $output = new BufferedOutput());
        self::assertSame($expected, $output->fetch());
    }
}
