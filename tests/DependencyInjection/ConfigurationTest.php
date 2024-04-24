<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\DependencyInjection;

use CyberSpectrum\I18NBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/** @covers \CyberSpectrum\I18NBundle\DependencyInjection\Configuration */
final class ConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        $processed = $processor->processConfiguration($configuration, [[]]);

        self::assertTrue($processed['enable_xliff']);
        self::assertTrue($processed['enable_memory']);
    }
}
