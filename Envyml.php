<?php declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brannow\Component\Envyml;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Process\Exception\ExceptionInterface as ProcessException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\ItemInterface;
use \Exception;

/**
 * Manages ~.env~ env.yaml files.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Benjamin Rannow <b.rannow@familie-redlich.de>
 */
final class Envyml
{
    /**
     * @var bool If `putenv()` should be used to define environment variables or not.
     *           Beware that `putenv()` is not thread safe and this setting will default
     *           to `false` in Symfony 5.0.
     */
    public function __construct(
        private bool $usePutenv = false
    )
    {}

    /**
     * Loads one or several .env files.
     *
     * @param string    $path       A file to load
     * @param ...string $extraPaths A list of additional files to load
     *
     * @throws Exception when a file has a syntax error
     * @throws Exception   when a file does not exist or is not readable
     */
    public function load(string $path, string ...$extraPaths): void
    {
        $this->doLoad(false, \func_get_args());
    }

    /**
     * Loads one or several .env files and enables override existing vars.
     *
     * @param string    $path       A file to load
     * @param ...string $extraPaths A list of additional files to load
     *
     * @throws Exception when a file has a syntax error
     * @throws Exception when a file does not exist or is not readable
     */
    public function overload(string $path, string ...$extraPaths): void
    {
        $this->doLoad(true, \func_get_args());
    }

    /**
     * Sets values as environment variables (via putenv, $_ENV, and $_SERVER).
     *
     * @param array $values               An array of env variables
     * @param bool  $overrideExistingVars Whether existing environment variables set by the system should be overridden
     */
    public function populate(array $values, bool $overrideExistingVars = false): void
    {
        $updateLoadedVars = false;
        $loadedVars = array_flip(explode(',', $_SERVER['SYMFONY_DOTENV_VARS'] ?? $_ENV['SYMFONY_DOTENV_VARS'] ?? ''));

        foreach ($values as $name => $value) {
            $notHttpName = !str_starts_with($name, 'HTTP_');
            if (isset($_SERVER[$name]) && $notHttpName && !isset($_ENV[$name])) {
                $_ENV[$name] = $_SERVER[$name];
            }

            // don't check existence with getenv() because of thread safety issues
            if (!isset($loadedVars[$name]) && !$overrideExistingVars && isset($_ENV[$name])) {
                continue;
            }

            if ($this->usePutenv) {
                putenv("$name=$value");
            }

            $_ENV[$name] = $value;
            if ($notHttpName) {
                $_SERVER[$name] = $value;
            }

            if (!isset($loadedVars[$name])) {
                $loadedVars[$name] = $updateLoadedVars = true;
            }
        }

        if ($updateLoadedVars) {
            unset($loadedVars['']);
            $loadedVars = implode(',', array_keys($loadedVars));
            $_ENV['SYMFONY_DOTENV_VARS'] = $_SERVER['SYMFONY_DOTENV_VARS'] = $loadedVars;

            if ($this->usePutenv) {
                putenv('SYMFONY_DOTENV_VARS='.$loadedVars);
            }
        }
    }

    private function doLoad(bool $overrideExistingVars, array $paths): void
    {
        $cache = new FilesystemAdapter();
        foreach ($paths as $path) {
            if (!is_readable($path) || is_dir($path)) {
                throw new FileLocatorFileNotFoundException($path);
            }

            $realPath = realPath($path);
            $cacheKey = basename($path).'_'.sha1($realPath).'_'.filemtime($realPath);

            // The callable will only be executed on a cache miss.
            $flattenData = $cache->get($cacheKey, function (ItemInterface $item) use ($realPath) {
                $item->expiresAfter(99999999);

                // ... do some HTTP request or heavy computations
                // refactor hardcoded the env stuff into root level
                $var = $this->translateArrayKeyValueIntoRoot(Yaml::parseFile($realPath), 'ENV');
                $flattenData = $this->flatArrayKeys($var);

                return $flattenData;
            });

            $this->populate($flattenData, $overrideExistingVars);
        }
    }

    /**
     * @param $ary
     * @param $key
     * @return array
     */
    private function translateArrayKeyValueIntoRoot($ary, $key): array
    {
        if (!empty($ary[$key])) {
            $subAry = $ary[$key];
            unset($ary[$key]);
            foreach ($subAry as $key => $value) {
                $ary[$key] = $value;
            }
        }

        return $ary;
    }

    /**
     * @param array $ary
     * @param string $keyPrefix
     * @param string $spacer
     * @return array
     */
    private function flatArrayKeys(array $ary, string $keyPrefix = '', $spacer = '_'): array
    {
        $finalArray = [];
        $kp = ($keyPrefix === '')? '': $keyPrefix.$spacer;
        foreach ($ary as $key => $value) {
           if (is_array($value)) {
                $subAry = $this->flatArrayKeys($value, $kp.$key, $spacer);
                foreach ($subAry as $k => $v) {
                    $finalArray[$k] = $v;
                }
           } else {
               $finalArray[$kp.$key] = (string)$value;
           }
        }

        return $finalArray;
    }
}
