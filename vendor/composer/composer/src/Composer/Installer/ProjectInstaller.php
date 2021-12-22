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

namespace Composer\Installer;

use Composer\Package\PackageInterface;
use Composer\Downloader\DownloadManager;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

/**
 * Project Installer is used to install a single package into a directory as
 * root project.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ProjectInstaller implements InstallerInterface
{
    /** @var string */
    private $installPath;
    /** @var DownloadManager */
    private $downloadManager;
    /** @var Filesystem */
    private $filesystem;

    /**
     * @param string $installPath
     */
    public function __construct($installPath, DownloadManager $dm, Filesystem $fs)
    {
        $this->installPath = rtrim(strtr($installPath, '\\', '/'), '/').'/';
        $this->downloadManager = $dm;
        $this->filesystem = $fs;
    }

    /**
     * Decides if the installer supports the given type
     *
     * @param  string $packageType
     * @return bool
     */
    public function supports($packageType)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function download(PackageInterface $package, PackageInterface $prevPackage = null)
    {
        $installPath = $this->installPath;
        if (file_exists($installPath) && !$this->filesystem->isDirEmpty($installPath)) {
            throw new \InvalidArgumentException("Project directory $installPath is not empty.");
        }
        if (!is_dir($installPath)) {
            mkdir($installPath, 0777, true);
        }

        return $this->downloadManager->download($package, $installPath, $prevPackage);
    }

    /**
     * @inheritDoc
     */
    public function prepare($type, PackageInterface $package, PackageInterface $prevPackage = null)
    {
        return $this->downloadManager->prepare($type, $package, $this->installPath, $prevPackage);
    }

    /**
     * @inheritDoc
     */
    public function cleanup($type, PackageInterface $package, PackageInterface $prevPackage = null)
    {
        return $this->downloadManager->cleanup($type, $package, $this->installPath, $prevPackage);
    }

    /**
     * @inheritDoc
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return $this->downloadManager->install($package, $this->installPath);
    }

    /**
     * @inheritDoc
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        throw new \InvalidArgumentException("not supported");
    }

    /**
     * @inheritDoc
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        throw new \InvalidArgumentException("not supported");
    }

    /**
     * Returns the installation path of a package
     *
     * @param  PackageInterface $package
     * @return string           path
     */
    public function getInstallPath(PackageInterface $package)
    {
        return $this->installPath;
    }
}
