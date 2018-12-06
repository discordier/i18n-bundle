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

namespace CyberSpectrum\I18NBundle\Test;

use CyberSpectrum\I18NBundle\CyberSpectrumI18NBundle;
use CyberSpectrum\I18NBundle\DependencyInjection\CompilerPass\CollectDictionaryProvidersPass;
use CyberSpectrum\I18NBundle\DependencyInjection\CyberSpectrumI18NExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This tests the bundle.
 *
 * @covers \CyberSpectrum\I18NBundle\CyberSpectrumI18NBundle
 */
class CyberSpectrumI18NBundleTest extends TestCase
{
    /**
     * Test that the compiler pass is added.
     *
     * @return void
     */
    public function testBuild(): void
    {
        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->setMethods(['addCompilerPass'])
            ->getMock();

        $arguments = [];
        $container
            ->expects($this->once())
            ->method('addCompilerPass')
            ->willReturnCallback(function () use (&$arguments) {
                $arguments[] = func_get_args();
            });

        $bundle = new CyberSpectrumI18NBundle();

        $bundle->build($container);

        $this->assertInstanceOf(CollectDictionaryProvidersPass::class, $arguments[0][0]);
    }

    /**
     * Test that the correct container extension is returned.
     *
     * @return void
     */
    public function testReturnsOwnContainerExtension(): void
    {
        $bundle    = new CyberSpectrumI18NBundle();
        $extension = $bundle->getContainerExtension();
        $this->assertInstanceOf(CyberSpectrumI18NExtension::class, $extension);
        // Ensure always the same extension is returned.
        $this->assertSame($extension, $bundle->getContainerExtension());
    }
}
