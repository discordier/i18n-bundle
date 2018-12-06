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

namespace CyberSpectrum\I18NBundle\Test\Configuration\Loader;

use CyberSpectrum\I18NBundle\Configuration\Loader\AbstractFileLoader;

/**
 * This is a dummy loader.
 */
class DummyLoader extends AbstractFileLoader
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
