<?php

declare(strict_types=1);

namespace CyberSpectrum\I18NBundle\Configuration\Loader;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

use function array_keys;
use function dirname;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_string;
use function pathinfo;
use function preg_replace;
use function restore_error_handler;
use function set_error_handler;

/**
 * This is loads job configuration files.
 *
 * @psalm-type TImportList=list<array{resource: string, type?: string, ignore_errors?: bool}|string>
 * @psalm-type TContentsArray=array{imports?: TImportList}
 */
final class YamlLoader extends AbstractFileLoader
{
    /**
     * Valid file extensions for yaml files.
     */
    public const FILE_EXTENSIONS = ['yaml', 'yml'];

    /** The yaml parser. */
    private ?Parser $yamlParser = null;

    /**
     * {@inheritDoc}
     */
    public function load($resource, $type = null): void
    {
        if (!is_string($resource)) {
            throw new InvalidArgumentException('Resource is not a string');
        }
        $path = $this->locator->locate($resource);
        if (!is_string($path)) {
            throw new InvalidArgumentException('Multiple files found');
        }
        $content = $this->loadFile($path);

        $this->checkArrayStructure($content, $resource);
        // imports
        $this->parseImports($content, $path);

        $this->setCurrentDir(dirname($path));
        $this->parseDefinitions($content, $path);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null): bool
    {
        if (!is_string($resource)) {
            return false;
        }

        if (null === $type && in_array(pathinfo($resource, PATHINFO_EXTENSION), self::FILE_EXTENSIONS, true)) {
            return true;
        }

        return in_array($type, self::FILE_EXTENSIONS, true);
    }

    /**
     * Parses all imports.
     *
     * @param TContentsArray $content The file content.
     * @param string         $file    The file name.
     *
     * @throws InvalidArgumentException When any import is invalid.
     */
    private function parseImports(array $content, string $file): void
    {
        if (!isset($content['imports'])) {
            return;
        }

        $defaultDirectory = dirname($file);
        foreach ($content['imports'] as $import) {
            if (!is_array($import)) {
                $import = ['resource' => $import];
            }

            $this->setCurrentDir($defaultDirectory);
            $this->import($import['resource'], ($import['type'] ?? null), ($import['ignore_errors'] ?? false), $file);
        }
    }

    /**
     * Loads a YAML file.
     *
     * @param string $file The file name.
     *
     * @return array The file content
     *
     * @throws RuntimeException When the yaml component is not installed.
     * @throws InvalidArgumentException When the given file is not a local file or when it does not exist.
     *
     * @psalm-suppress UnusedVariable - false positives in setting the error handler.
     */
    protected function loadFile(string $file): array
    {
        if (!class_exists(Parser::class)) {
            throw new RuntimeException(
                'Unable to load YAML config files as the Symfony Yaml Component is not installed.'
            );
        }

        if (!stream_is_local($file)) {
            throw new InvalidArgumentException(sprintf('This is not a local file "%s".', $file));
        }

        if (!file_exists($file)) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist.', $file));
        }

        if (null === $this->yamlParser) {
            $this->yamlParser = new Parser();
        }

        $prevErrorHandler = set_error_handler(
            function (
                int $level,
                string $message,
                ?string $script,
                ?int $line,
                ?array $context
            ) use (
                $file,
                &$prevErrorHandler
            ): bool {
                $message = E_USER_DEPRECATED === $level
                    ? preg_replace('/ on line \d+/', ' in "' . $file . '"$0', $message)
                    : $message;
                if (is_callable($prevErrorHandler)) {
                    return (bool) $prevErrorHandler($level, $message, $script, $line, $context);
                }

                return false;
            }
        );

        try {
            /** @var mixed $configuration */
            $configuration = $this->yamlParser->parseFile($file, (Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS));
        } catch (ParseException $e) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
        } finally {
            restore_error_handler();
        }

        return $this->validate($configuration, $file);
    }

    /**
     * Validates a YAML file.
     *
     * @param mixed  $content The file content.
     * @param string $file    The file name.
     *
     * @return TContentsArray
     *
     * @throws InvalidArgumentException When file is not valid.
     */
    private function validate($content, string $file): array
    {
        if (!is_array($content)) {
            throw new InvalidArgumentException(
                sprintf('The config file "%s" is not valid. It should contain an array. Check your YAML syntax.', $file)
            );
        }

        $this->checkArrayStructure($content, $file);

        return $content;
    }

    /**
     * @psalm-assert TContentsArray $content
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function checkArrayStructure(array $content, string $file): void
    {
        foreach (array_keys($content) as $namespace) {
            if (in_array($namespace, ['imports', 'dictionaries', 'jobs'])) {
                continue;
            }

            throw new InvalidArgumentException(sprintf('Unknown configuration key "%s".', $namespace));
        }

        if ($imports = $content['imports'] ?? null) {
            if (!is_array($imports)) {
                throw $this->buildException('The "imports" key must contain an array', $file);
            }
            /** @var mixed $import */
            foreach ($imports as $import) {
                if (is_string($import)) {
                    continue;
                }
                if (is_array($import)) {
                    if (!is_string($import['resource'] ?? null)) {
                        throw $this->buildException('An import should provide a resource', $file);
                    }
                    if (!is_bool($import['ignore_errors'] ?? false)) {
                        throw $this->buildException('ignore_errors in imports must be bool', $file);
                    }
                }
                throw $this->buildException('An import must be string or array providing a resource', $file);
            }
        }
    }

    private function buildException(string $message, string $file): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf($message . ' in %s. Check your YAML syntax.', $file));
    }
}
