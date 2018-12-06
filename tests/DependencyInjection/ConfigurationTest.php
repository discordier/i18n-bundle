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

namespace CyberSpectrum\I18NBundle\Test\DependencyInjection;

use CyberSpectrum\I18NBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * This tests the bundle.
 *
 * @covers \CyberSpectrum\I18NBundle\DependencyInjection\Configuration
 */
class ConfigurationTest extends TestCase
{
    /**
     * Test loading of the configuration.
     *
     * @return void
     */
    public function testDefaults(): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        $processed = $processor->processConfiguration($configuration, [[]]);

        $this->assertTrue($processed['enable_xliff']);
        $this->assertTrue($processed['enable_memory']);
    }
}
