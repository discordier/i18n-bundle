<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Test\Configuration\Loader;

use CyberSpectrum\I18NBundle\Configuration\Loader\AbstractFileLoader;

/**
 * This is a dummy loader.
 */
final class DummyLoader extends AbstractFileLoader
{
    /**
     * {@inheritDoc}
     */
    public function load($resource, $type = null): void
    {
        $this->parseDefinitions($resource, '/dummy/config');
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null): bool
    {
        return true;
    }
}
