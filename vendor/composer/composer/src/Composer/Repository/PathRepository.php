<?php

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Composer\Repository;

use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Version\VersionGuesser;
use Composer\Package\Version\VersionParser;
use Composer\Util\Platform;
use Composer\Util\ProcessExecutor;
use Composer\Util\Filesystem;
use Composer\Util\Url;
use Composer\Util\Git as GitUtil;

/**
 * This repository allows installing local packages that are not necessarily under their own VCS.
 *
 * The local packages will be symlinked when possible, else they will be copied.
 *
 * @code
 * "require": {
 *     "<vendor>/<local-package>": "*"
 * },
 * "repositories": [
 *     {
 *         "type": "path",
 *         "url": "../../relative/path/to/package/"
 *     },
 *     {
 *         "type": "path",
 *         "url": "/absolute/path/to/package/"
 *     },
 *     {
 *         "type": "path",
 *         "url": "/absolute/path/to/several/packages/*"
 *     },
 *     {
 *         "type": "path",
 *         "url": "../../relative/path/to/package/",
 *         "options": {
 *             "symlink": false
 *         }
 *     },
 * ]
 * @endcode
 *
 * @author Samuel Roze <samuel.roze@gmail.com>
 * @author Johann Reinke <johann.reinke@gmail.com>
 */
class PathRepository extends ArrayRepository implements ConfigurableRepositoryInterface
{
    /**
     * @var ArrayLoader
     */
    private $loader;

    /**
     * @var VersionGuesser
     */
    private $versionGuesser;

    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $repoConfig;

    /**
     * @var ProcessExecutor
     */
    private $process;

    /**
     * @var array
     */
    private $options;

    /**
     * Initializes path repository.
     *
     * @param array       $repoConfig
     * @param IOInterface $io
     * @param Config      $config
     */
    public function __construct(array $repoConfig, IOInterface $io, Config $config)
    {
        if (!isset($repoConfig['url'])) {
            throw new \RuntimeException('You must specify the `url` configuration for the path repository');
        }

        $this->loader = new ArrayLoader(null, true);
        $this->url = Platform::expandPath($repoConfig['url']);
        $this->process = new ProcessExecutor($io);
        $this->versionGuesser = new VersionGuesser($config, $this->process, new VersionParser());
        $this->repoConfig = $repoConfig;
        $this->options = isset($repoConfig['options']) ? $repoConfig['options'] : array();
        if (!isset($this->options['relative'])) {
            $filesystem = new Filesystem();
            $this->options['relative'] = !$filesystem->isAbsolutePath($this->url);
        }

        parent::__construct();
    }

    public function getRepoName()
    {
        return 'path repo ('.Url::sanitize($this->repoConfig['url']).')';
    }

    public function getRepoConfig()
    {
        return $this->repoConfig;
    }

    /**
     * Initializes path repository.
     *
     * This method will basically read the folder and add the found package.
     */
    protected function initialize()
    {
        parent::initialize();

        $urlMatches = $this->getUrlMatches();

        if (empty($urlMatches)) {
            if (preg_match('{[*{}]}', $this->url)) {
                $url = $this->url;
                while (preg_match('{[*{}]}', $url)) {
                    $url = dirname($url);
                }
                // the parent directory before any wildcard exists, so we assume it is correctly configured but simply empty
                if (is_dir($url)) {
                    return;
                }
            }

            throw new \RuntimeException('The `url` supplied for the path (' . $this->url . ') repository does not exist');
        }

        foreach ($urlMatches as $url) {
            $path = realpath($url) . DIRECTORY_SEPARATOR;
            $composerFilePath = $path.'composer.json';

            if (!file_exists($composerFilePath)) {
                continue;
            }

            $json = file_get_contents($composerFilePath);
            $package = JsonFile::parseJson($json, $composerFilePath);
            $package['dist'] = array(
                'type' => 'path',
                'url' => $url,
                'reference' => sha1($json . serialize($this->options)),
            );
            $package['transport-options'] = $this->options;
            unset($package['transport-options']['versions']);

            // use the version provided as option if available
            if (isset($package['name'], $this->options['versions'][$package['name']])) {
                $package['version'] = $this->options['versions'][$package['name']];
            }

            // carry over the root package version if this path repo is in the same git repository as root package
            if (!isset($package['version']) && ($rootVersion = getenv('COMPOSER_ROOT_VERSION'))) {
                if (
                    0 === $this->process->execute('git rev-parse HEAD', $ref1, $path)
                    && 0 === $this->process->execute('git rev-parse HEAD', $ref2)
                    && $ref1 === $ref2
                ) {
                    $package['version'] = $rootVersion;
                }
            }

            $output = '';
            if (is_dir($path . DIRECTORY_SEPARATOR . '.git') && 0 === $this->process->execute('git log -n1 --pretty=%H'.GitUtil::getNoShowSignatureFlag($this->process), $output, $path)) {
                $package['dist']['reference'] = trim($output);
            }

            if (!isset($package['version'])) {
                $versionData = $this->versionGuesser->guessVersion($package, $path);
                if (is_array($versionData) && $versionData['pretty_version']) {
                    // if there is a feature branch detected, we add a second packages with the feature branch version
                    if (!empty($versionData['feature_pretty_version'])) {
                        $package['version'] = $versionData['feature_pretty_version'];
                        $this->addPackage($this->loader->load($package));
                    }

                    $package['version'] = $versionData['pretty_version'];
                } else {
                    $package['version'] = 'dev-master';
                }
            }

            $package = $this->loader->load($package);
            $this->addPackage($package);
        }
    }

    /**
     * Get a list of all (possibly relative) path names matching given url (supports globbing).
     *
     * @return string[]
     */
    private function getUrlMatches()
    {
        $flags = GLOB_MARK | GLOB_ONLYDIR;

        if (defined('GLOB_BRACE')) {
            $flags |= GLOB_BRACE;
        } elseif (strpos($this->url, '{') !== false || strpos($this->url, '}') !== false) {
            throw new \RuntimeException('The operating system does not support GLOB_BRACE which is required for the url '. $this->url);
        }

        // Ensure environment-specific path separators are normalized to URL separators
        return array_map(function ($val) {
            return rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $val), '/');
        }, glob($this->url, $flags));
    }
}
