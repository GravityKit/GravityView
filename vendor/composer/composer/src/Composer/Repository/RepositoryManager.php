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

use Composer\IO\IOInterface;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Package\PackageInterface;
use Composer\Util\HttpDownloader;
use Composer\Util\ProcessExecutor;

/**
 * Repositories manager.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author François Pluchino <francois.pluchino@opendisplay.com>
 */
class RepositoryManager
{
    /** @var InstalledRepositoryInterface */
    private $localRepository;
    /** @var list<RepositoryInterface> */
    private $repositories = array();
    /** @var array<string, string> */
    private $repositoryClasses = array();
    /** @var IOInterface */
    private $io;
    /** @var Config */
    private $config;
    /** @var HttpDownloader */
    private $httpDownloader;
    /** @var ?EventDispatcher */
    private $eventDispatcher;
    /** @var ProcessExecutor */
    private $process;

    public function __construct(IOInterface $io, Config $config, HttpDownloader $httpDownloader, EventDispatcher $eventDispatcher = null, ProcessExecutor $process = null)
    {
        $this->io = $io;
        $this->config = $config;
        $this->httpDownloader = $httpDownloader;
        $this->eventDispatcher = $eventDispatcher;
        $this->process = $process ?: new ProcessExecutor($io);
    }

    /**
     * Searches for a package by its name and version in managed repositories.
     *
     * @param string                                                 $name       package name
     * @param string|\Composer\Semver\Constraint\ConstraintInterface $constraint package version or version constraint to match against
     *
     * @return PackageInterface|null
     */
    public function findPackage($name, $constraint)
    {
        foreach ($this->repositories as $repository) {
            /** @var RepositoryInterface $repository */
            if ($package = $repository->findPackage($name, $constraint)) {
                return $package;
            }
        }

        return null;
    }

    /**
     * Searches for all packages matching a name and optionally a version in managed repositories.
     *
     * @param string                                                 $name       package name
     * @param string|\Composer\Semver\Constraint\ConstraintInterface $constraint package version or version constraint to match against
     *
     * @return PackageInterface[]
     */
    public function findPackages($name, $constraint)
    {
        $packages = array();

        foreach ($this->getRepositories() as $repository) {
            $packages = array_merge($packages, $repository->findPackages($name, $constraint));
        }

        return $packages;
    }

    /**
     * Adds repository
     *
     * @param RepositoryInterface $repository repository instance
     */
    public function addRepository(RepositoryInterface $repository)
    {
        $this->repositories[] = $repository;
    }

    /**
     * Adds a repository to the beginning of the chain
     *
     * This is useful when injecting additional repositories that should trump Packagist, e.g. from a plugin.
     *
     * @param RepositoryInterface $repository repository instance
     */
    public function prependRepository(RepositoryInterface $repository)
    {
        array_unshift($this->repositories, $repository);
    }

    /**
     * Returns a new repository for a specific installation type.
     *
     * @param  string                    $type   repository type
     * @param  array                     $config repository configuration
     * @param  string                    $name   repository name
     * @throws \InvalidArgumentException if repository for provided type is not registered
     * @return RepositoryInterface
     */
    public function createRepository($type, $config, $name = null)
    {
        if (!isset($this->repositoryClasses[$type])) {
            throw new \InvalidArgumentException('Repository type is not registered: '.$type);
        }

        if (isset($config['packagist']) && false === $config['packagist']) {
            $this->io->writeError('<warning>Repository "'.$name.'" ('.json_encode($config).') has a packagist key which should be in its own repository definition</warning>');
        }

        $class = $this->repositoryClasses[$type];

        if (isset($config['only']) || isset($config['exclude']) || isset($config['canonical'])) {
            $filterConfig = $config;
            unset($config['only'], $config['exclude'], $config['canonical']);
        }

        $repository = new $class($config, $this->io, $this->config, $this->httpDownloader, $this->eventDispatcher, $this->process);

        if (isset($filterConfig)) {
            $repository = new FilterRepository($repository, $filterConfig);
        }

        return $repository;
    }

    /**
     * Stores repository class for a specific installation type.
     *
     * @param string $type  installation type
     * @param string $class class name of the repo implementation
     */
    public function setRepositoryClass($type, $class)
    {
        $this->repositoryClasses[$type] = $class;
    }

    /**
     * Returns all repositories, except local one.
     *
     * @return RepositoryInterface[]
     */
    public function getRepositories()
    {
        return $this->repositories;
    }

    /**
     * Sets local repository for the project.
     *
     * @param InstalledRepositoryInterface $repository repository instance
     */
    public function setLocalRepository(InstalledRepositoryInterface $repository)
    {
        $this->localRepository = $repository;
    }

    /**
     * Returns local repository for the project.
     *
     * @return InstalledRepositoryInterface
     */
    public function getLocalRepository()
    {
        return $this->localRepository;
    }
}
