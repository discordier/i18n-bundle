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

use CyberSpectrum\I18NBundle\Command\DebugProvidersCommand;
use CyberSpectrum\I18N\DependencyInjection\IdProvidingServiceLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * This tests the debug command.
 *
 * @covers \CyberSpectrum\I18NBundle\Command\DebugProvidersCommand
 */
class DebugProvidersCommandTest extends TestCase
{
    /**
     * Test the default execution.
     *
     * @return void
     */
    public function testConfigure(): void
    {
        $providers = new IdProvidingServiceLocator([]);

        $command = new DebugProvidersCommand($providers);

        $this->assertSame('debug:i18n-providers', $command->getName());
        $this->assertSame('List dictionary providers', $command->getDescription());
    }

    /**
     * Test the default execution.
     *
     * @return void
     */
    public function testDefaultExecution(): void
    {
        $providers = new IdProvidingServiceLocator([
            'provider1' => \Closure::fromCallable(function () {}),
            'provider2' => \Closure::fromCallable(function () {}),
            'provider3' => \Closure::fromCallable(function () {}),
        ]);

        $command = new DebugProvidersCommand($providers);

        $expected = <<<EOF
provider1
provider2
provider3

EOF;

        $command->run(new ArrayInput([]), $output = new BufferedOutput());
        $this->assertSame($expected, $output->fetch());
    }
}
