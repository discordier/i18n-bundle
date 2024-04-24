<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Configuration\Loader;

use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder;
use CyberSpectrum\I18N\Configuration\LoaderInterface;
use Exception;
use Generator;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Config\Exception\FileLoaderImportCircularReferenceException;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Throwable;

use function count;
use function dirname;
use function is_array;
use function is_string;
use function str_contains;
use function strcspn;
use function strlen;
use function substr;

/**
 * This is loads job configuration files.
 *
 * Largely based upon symfony file loader which is written by Fabien Potencier <fabien@symfony.com>.
 *
 * @psalm-type TContentsArray=array{
 *   dictionaries?: array<string, array{name?: string, dictionary?: string, type: string}>,
 *   jobs?: array<string, array{type: string}>
 * }
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractFileLoader implements LoaderInterface
{
    /** @var array<string, bool> */
    protected static array $loading = [];

    protected FileLocatorInterface $locator;

    private ?string $currentDir = null;

    /** The configuration being loaded. */
    private Configuration $configuration;

    /** The services for building definitions. */
    private DefinitionBuilder $definitionBuilder;

    /**
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

    /** Obtain the configuration. */
    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * Sets the current directory.
     *
     * @param string $dir The directory to use.
     */
    public function setCurrentDir(string $dir): void
    {
        $this->currentDir = $dir;
    }

    /** Returns the file locator used by this loader. */
    public function getLocator(): FileLocatorInterface
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
     * @return void
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
        if (is_string($resource) && strlen($resource) !== $length = strcspn($resource, '*?{[')) {
            $excluded = [];
            foreach ((array) $exclude as $pattern) {
                /** @psalm-suppress InvalidIterator - Psalm thinks a Generator is not iterable, why? */
                foreach ($this->glob($pattern, true, $resources, false, true) as $path => $_ignored) {
                    // normalize Windows slashes
                    $excluded[str_replace('\\', '/', $path)] = true;
                }
            }

            $isSubPath = 0 !== $length && str_contains(substr($resource, 0, $length), '/');
            /** @psalm-suppress InvalidIterator - Psalm thinks a Generator is not iterable, why? */
            foreach (
                $this->glob(
                    $resource,
                    false,
                    $resources,
                    $ignoreErrors || !$isSubPath,
                    false,
                    $excluded
                ) as $path => $_ignored
            ) {
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
     * @return Generator<string, SplFileInfo, void, null>
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
    ): Generator {
        if (strlen($pattern) === $index = strcspn($pattern, '*?{[')) {
            $prefix  = $pattern;
            $pattern = '';
        } elseif (0 === $index || !str_contains(substr($pattern, 0, $index), '/')) {
            $prefix  = '.';
            $pattern = '/' . $pattern;
        } else {
            $prefix  = dirname(substr($pattern, 0, (1 + $index)));
            $pattern = substr($pattern, strlen($prefix));
        }

        try {
            $prefix = $this->locator->locate($prefix, $this->currentDir, true);
        } catch (FileLocatorFileNotFoundException $e) {
            if (!$ignoreErrors) {
                throw $e;
            }

            $resource = [];
            /** @var list<string> $paths */
            $paths = $e->getPaths();
            foreach ($paths as $path) {
                $resource[] = new FileExistenceResource($path);
            }
            return;
        }

        $resource = new GlobResource($prefix, $pattern, $recursive, $forExclusion, $excluded);

        foreach ($resource->getIterator() as $filename => $fileInfo) {
            yield $filename => $fileInfo;
        }
    }

    /**
     * Parse the configuration definitions.
     *
     * @param array  $definitions The definitions to parse.
     * @param string $path        The configuration path.
     *
     * @throws RuntimeException When the config is invalid.
     */
    protected function parseDefinitions(array $definitions, string $path): void
    {
        try {
            $this->checkArrayStructure($definitions, $path);
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
                        throw new RuntimeException('Unknown dictionary type ' . $definition['type'], 0, $exception);
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
                        throw new RuntimeException('Unknown job type ' . $definition['type']);
                    }
                }
            }
        } catch (Throwable $previous) {
            throw new RuntimeException('Invalid configuration in ' . $path, 0, $previous);
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
     * @throws FileLoaderImportCircularReferenceException When a circular import chain has been found.
     * @throws LoaderLoadException                        For anything else that goes wrong.
     * @throws Exception                                 Get's converted to LoaderLoadException.
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
                assert(is_string($resource));
                $resource = $this->getLocator()->locate($resource, $this->currentDir, false);
            }

            $resources      = is_array($resource) ? $resource : [$resource];
            $resourcesCount = count($resources);
            $currentResource = (string) $resources[0];
            for ($i = 0; $i < $resourcesCount; ++$i) {
                $currentResource = (string) $resources[$i];
                if (isset(self::$loading[$currentResource])) {
                    if ($i == ($resourcesCount - 1)) {
                        throw new FileLoaderImportCircularReferenceException(array_keys(self::$loading));
                    }
                } else {
                    $resource = $currentResource;
                    break;
                }
            }
            self::$loading[$currentResource] = true;

            try {
                $this->load($currentResource, $type);
            } finally {
                unset(self::$loading[$currentResource]);
            }

            return;
        } catch (FileLoaderImportCircularReferenceException $e) {
            throw $e;
        } catch (Exception $e) {
            if (!$ignoreErrors) {
                // prevent embedded imports from nesting multiple exceptions
                if ($e instanceof LoaderLoadException) {
                    throw $e;
                }

                throw new LoaderLoadException(var_export($resource, true), $sourceResource, 0, $e, $type);
            }
        }
    }

    /**
     * @psalm-assert TContentsArray $content
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function checkArrayStructure(array $content, string $file): void
    {
        if (null !== ($dictionaries = $content['dictionaries'] ?? null)) {
            if (!is_array($dictionaries)) {
                throw $this->buildException('The "dictionaries" key must contain an array', $file);
            }
            /** @var mixed $dictionary */
            foreach ($dictionaries as $dictionary) {
                if (!is_array($dictionary)) {
                    throw $this->buildException('A dictionary must be array', $file);
                }
                if (array_key_exists('name', $dictionary) && !is_string($dictionary['name'])) {
                    throw $this->buildException('A dictionary name must be an string', $file);
                }
                if (array_key_exists('dictionary', $dictionary) && !is_string($dictionary['dictionary'])) {
                    throw $this->buildException('A dictionary dictionary must be an string', $file);
                }
            }
        }
        if (null !== ($jobs = $content['jobs'] ?? null)) {
            if (!is_array($jobs)) {
                throw $this->buildException('The "jobs" key must contain an array', $file);
            }
        }
    }

    private function buildException(string $message, string $file): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf($message . ' in %s. Check your YAML syntax.', $file));
    }
}
