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

namespace CyberSpectrum\I18NBundle\Configuration\Loader;

use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder;
use CyberSpectrum\I18N\Configuration\LoaderInterface;
use Symfony\Component\Config\Exception\FileLoaderImportCircularReferenceException;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * This is loads job configuration files.
 *
 * Largely based upon symfony file loader which is written by Fabien Potencier <fabien@symfony.com>.
 */
abstract class AbstractFileLoader implements LoaderInterface
{
    /** @var string[] */
    protected static $loading = [];

    /** @var FileLocatorInterface */
    protected $locator;

    /** @var string|null */
    private $currentDir;

    /**
     * The configuration being loaded.
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * The services for building definitions.
     *
     * @var DefinitionBuilder
     */
    private $definitionBuilder;

    /**
     * Create a new instance.
     *
     * @param Configuration        $configuration     The configuration to load.
     * @param FileLocatorInterface $locator           The file locator.
     * @param DefinitionBuilder    $definitionBuilder The definition builder.
     */
    public function __construct(
        Configuration $configuration,
        FileLocatorInterface $locator,
        DefinitionBuilder $definitionBuilder
    ) {
        $this->configuration     = $configuration;
        $this->locator           = $locator;
        $this->definitionBuilder = $definitionBuilder;
    }

    /**
     * Obtain the configuration.
     *
     * @return Configuration
     */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * Sets the current directory.
     *
     * @param string $dir The directory to use.
     *
     * @return void
     */
    public function setCurrentDir(string $dir): void
    {
        $this->currentDir = $dir;
    }

    /**
     * Returns the file locator used by this loader.
     *
     * @return FileLocatorInterface
     */
    public function getLocator()
    {
        return $this->locator;
    }

    /**
     * Imports a resource.
     *
     * @param mixed                $resource       A Resource.
     * @param string|null          $type           The resource type or null if unknown.
     * @param bool                 $ignoreErrors   Whether to ignore import errors or not.
     * @param string|null          $sourceResource The original resource importing the new resource.
     * @param string|string[]|null $exclude        Glob patterns to exclude from the import.
     *
     * @return mixed
     *
     * @throws LoaderLoadException                        If no loader is found or anything else that went wrong.
     * @throws FileLoaderImportCircularReferenceException When a circular import chain has been found.
     * @throws FileLocatorFileNotFoundException           When the resource could not be found.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function import(
        $resource,
        string $type = null,
        bool $ignoreErrors = false,
        string $sourceResource = null,
        $exclude = null
    ): void {
        if (\is_string($resource) && \strlen($resource) !== $length = strcspn($resource, '*?{[')) {
            $excluded = [];
            foreach ((array) $exclude as $pattern) {
                foreach ($this->glob($pattern, true, $resources, false, true) as $path => $info) {
                    // normalize Windows slashes
                    $excluded[str_replace('\\', '/', $path)] = true;
                }
            }

            $isSubPath = 0 !== $length && false !== strpos(substr($resource, 0, $length), '/');
            foreach ($this->glob(
                $resource,
                false,
                $resources,
                $ignoreErrors || !$isSubPath,
                false,
                $excluded
            ) as $path => $info) {
                $this->doImport($path, 'glob' === $type ? null : $type, $ignoreErrors, $sourceResource);
                $isSubPath = true;
            }

            if ($isSubPath) {
                return;
            }
        }

        $this->doImport($resource, $type, $ignoreErrors, $sourceResource);
    }

    /**
     * Glob for the resource.
     *
     * @param string $pattern      The glob pattern to search for.
     * @param bool   $recursive    Flag whether directories should be scanned recursively or not.
     * @param mixed  $resource     The resources found.
     * @param bool   $ignoreErrors Flag whether errors should get ignored.
     * @param bool   $forExclusion Flag if the resources should get collected for exclusion.
     * @param array  $excluded     List of prefixes to exclude.
     *
     * @return \Generator
     *
     * @throws FileLocatorFileNotFoundException When the resource could not be found.
     *
     * @internal
     */
    protected function glob(
        string $pattern,
        bool $recursive,
        &$resource = null,
        bool $ignoreErrors = false,
        bool $forExclusion = false,
        array $excluded = []
    ): \Generator {
        if (\strlen($pattern) === $index = strcspn($pattern, '*?{[')) {
            $prefix  = $pattern;
            $pattern = '';
        } elseif (0 === $index || false === strpos(substr($pattern, 0, $index), '/')) {
            $prefix  = '.';
            $pattern = '/'.$pattern;
        } else {
            $prefix  = \dirname(substr($pattern, 0, (1 + $index)));
            $pattern = substr($pattern, \strlen($prefix));
        }

        try {
            $prefix = $this->locator->locate($prefix, $this->currentDir, true);
        } catch (FileLocatorFileNotFoundException $e) {
            if (!$ignoreErrors) {
                throw $e;
            }

            $resource = [];
            foreach ($e->getPaths() as $path) {
                $resource[] = new FileExistenceResource($path);
            }
            // @codingStandardsIgnoreStart - does not understand the generator
            return;
            // @codingStandardsIgnoreEnd
        }
        $resource = new GlobResource($prefix, $pattern, $recursive, $forExclusion, $excluded);

        yield from $resource;
    }

    /**
     * Parse the configuration definitions.
     *
     * @param array  $definitions The definitions to parse.
     * @param string $path        The configuration path.
     *
     * @return void
     *
     * @throws \RuntimeException When the config is invalid.
     */
    protected function parseDefinitions(array $definitions, string $path): void
    {
        try {
            if (isset($definitions['dictionaries'])) {
                foreach ($definitions['dictionaries'] as $name => $definition) {
                    if (isset($definition['name'])) {
                        $definition['dictionary'] = $definition['name'];
                    }
                    $definition['name'] = $name;
                    try {
                        $this->configuration->setDictionary(
                            $this->definitionBuilder->buildDictionary($this->configuration, $definition)
                        );
                    } catch (ServiceNotFoundException $exception) {
                        throw new \RuntimeException('Unknown dictionary type ' . $definition['type'], 0, $exception);
                    }
                }
            }

            if (isset($definitions['jobs'])) {
                foreach ($definitions['jobs'] as $name => $definition) {
                    $definition['name'] = $name;
                    try {
                        $this->configuration->setJob(
                            $this->definitionBuilder->buildJob($this->configuration, $definition)
                        );
                    } catch (ServiceNotFoundException $exception) {
                        throw new \RuntimeException('Unknown job type ' . $definition['type']);
                    }
                }
            }
        } catch (\Throwable $previous) {
            throw new \RuntimeException('Invalid configuration in ' . $path, 0, $previous);
        }
    }

    /**
     * Import a resource.
     *
     * @param mixed       $resource       The resource to import.
     * @param string|null $type           The type of the resource.
     * @param bool        $ignoreErrors   Flag if errors shall be ignored.
     * @param string|null $sourceResource The source resource importing the resource.
     *
     * @return void
     *
     * @throws FileLoaderImportCircularReferenceException When a circular import chain has been found.
     * @throws LoaderLoadException                        For anything else that goes wrong.
     * @throws \Exception                                 Get's converted to LoaderLoadException.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function doImport(
        $resource,
        string $type = null,
        bool $ignoreErrors = false,
        string $sourceResource = null
    ): void {
        try {
            if (null !== $this->currentDir) {
                $resource = $this->getLocator()->locate($resource, $this->currentDir, false);
            }

            $resources      = \is_array($resource) ? $resource : [$resource];
            $resourcesCount = \count($resources);
            for ($i = 0; $i < $resourcesCount; ++$i) {
                if (isset(self::$loading[$resources[$i]])) {
                    if ($i == ($resourcesCount - 1)) {
                        throw new FileLoaderImportCircularReferenceException(array_keys(self::$loading));
                    }
                } else {
                    $resource = $resources[$i];
                    break;
                }
            }
            self::$loading[$resource] = true;

            try {
                $this->load($resource, $type);
            } finally {
                unset(self::$loading[$resource]);
            }

            return;
        } catch (FileLoaderImportCircularReferenceException $e) {
            throw $e;
        } catch (\Exception $e) {
            if (!$ignoreErrors) {
                // prevent embedded imports from nesting multiple exceptions
                if ($e instanceof LoaderLoadException) {
                    throw $e;
                }

                throw new LoaderLoadException($resource, $sourceResource, null, $e, $type);
            }
        }
    }
}
