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

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

/**
 * This is loads job configuration files.
 */
class YamlLoader extends AbstractFileLoader
{
    /**
     * Valid file extensions for yaml files.
     */
    public const FILE_EXTENSIONS = ['yaml', 'yml'];

    /**
     * The yaml parser.
     *
     * @var Parser
     */
    private $yamlParser;

    /**
     * {@inheritDoc}
     */
    public function load($resource, $type = null): void
    {
        $path = $this->locator->locate($resource);

        $content = $this->loadFile($path);

        // empty file
        if (null === $content) {
            return;
        }

        // imports
        $this->parseImports($content, $path);

        $this->setCurrentDir(\dirname($path));
        $this->parseDefinitions($content, $path);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($resource, $type = null): bool
    {
        if (!\is_string($resource)) {
            return false;
        }

        if (null === $type && \in_array(pathinfo($resource, PATHINFO_EXTENSION), static::FILE_EXTENSIONS, true)) {
            return true;
        }

        return \in_array($type, static::FILE_EXTENSIONS, true);
    }

    /**
     * Parses all imports.
     *
     * @param array  $content The file content.
     * @param string $file    The file name.
     *
     * @return void
     *
     * @throws \InvalidArgumentException When any import is invalid.
     */
    private function parseImports(array $content, $file): void
    {
        if (!isset($content['imports'])) {
            return;
        }

        if (!\is_array($content['imports'])) {
            throw new \InvalidArgumentException(
                sprintf('The "imports" key should contain an array in %s. Check your YAML syntax.', $file)
            );
        }

        $defaultDirectory = \dirname($file);
        foreach ($content['imports'] as $import) {
            if (!\is_array($import)) {
                $import = array('resource' => $import);
            }
            if (!isset($import['resource'])) {
                throw new \InvalidArgumentException(
                    sprintf('An import should provide a resource in %s. Check your YAML syntax.', $file)
                );
            }

            $this->setCurrentDir($defaultDirectory);
            $this->import(
                $import['resource'],
                ($import['type'] ?? null),
                isset($import['ignore_errors']) ? (bool) $import['ignore_errors'] : false,
                $file
            );
        }
    }

    /**
     * Loads a YAML file.
     *
     * @param string $file The file name.
     *
     * @return array The file content
     *
     * @throws \RuntimeException When the yaml component is not installed.
     * @throws \InvalidArgumentException When the given file is not a local file or when it does not exist.
     */
    protected function loadFile($file): array
    {
        if (!class_exists(Parser::class)) {
            throw new \RuntimeException(
                'Unable to load YAML config files as the Symfony Yaml Component is not installed.'
            );
        }

        if (!stream_is_local($file)) {
            throw new \InvalidArgumentException(sprintf('This is not a local file "%s".', $file));
        }

        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not exist.', $file));
        }

        if (null === $this->yamlParser) {
            $this->yamlParser = new Parser();
        }

        $prevErrorHandler = set_error_handler(
            function ($level, $message, $script, $line) use ($file, &$prevErrorHandler) {
                $message = E_USER_DEPRECATED === $level
                    ? preg_replace('/ on line \d+/', ' in "'.$file.'"$0', $message)
                    : $message;
    
                return $prevErrorHandler ? $prevErrorHandler($level, $message, $script, $line) : false;
            }
        );

        try {
            $configuration = $this->yamlParser->parseFile($file, (Yaml::PARSE_CONSTANT | Yaml::PARSE_CUSTOM_TAGS));
        } catch (ParseException $e) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not contain valid YAML.', $file), 0, $e);
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
     * @return array
     *
     * @throws \InvalidArgumentException When file is not valid.
     */
    private function validate($content, $file): array
    {
        if (!\is_array($content)) {
            throw new \InvalidArgumentException(
                sprintf('The config file "%s" is not valid. It should contain an array. Check your YAML syntax.', $file)
            );
        }

        foreach (\array_keys($content) as $namespace) {
            if (\in_array($namespace, array('imports', 'jobs', 'dictionaries'))) {
                continue;
            }

            throw new \InvalidArgumentException(sprintf('Unknown configuration key "%s".', $namespace));
        }

        return $content;
    }
}
