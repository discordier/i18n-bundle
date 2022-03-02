<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test;

use CyberSpectrum\I18NBundle\CyberSpectrumI18NBundle;
use CyberSpectrum\I18NBundle\DependencyInjection\CompilerPass\CollectDictionaryProvidersPass;
use CyberSpectrum\I18NBundle\DependencyInjection\CyberSpectrumI18NExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/** @covers \CyberSpectrum\I18NBundle\CyberSpectrumI18NBundle */
final class CyberSpectrumI18NBundleTest extends TestCase
{
    /** Test that the compiler pass is added. */
    public function testBuild(): void
    {
        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->onlyMethods(['addCompilerPass'])
            ->getMock();

        $arguments = [];
        $container
            ->expects($this->once())
            ->method('addCompilerPass')
            ->willReturnCallback(function () use (&$arguments, $container): ContainerBuilder {
                $arguments[] = func_get_args();

                return $container;
            });

        $bundle = new CyberSpectrumI18NBundle();

        $bundle->build($container);

        self::assertInstanceOf(CollectDictionaryProvidersPass::class, $arguments[0][0]);
    }

    /** Test that the correct container extension is returned. */
    public function testReturnsOwnContainerExtension(): void
    {
        $bundle    = new CyberSpectrumI18NBundle();
        $extension = $bundle->getContainerExtension();
        self::assertInstanceOf(CyberSpectrumI18NExtension::class, $extension);
        // Ensure always the same extension is returned.
        self::assertSame($extension, $bundle->getContainerExtension());
    }
}
