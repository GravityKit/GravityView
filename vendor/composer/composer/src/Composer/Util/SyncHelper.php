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

namespace Composer\Util;

use Composer\Downloader\DownloaderInterface;
use Composer\Package\PackageInterface;
use React\Promise\PromiseInterface;

class SyncHelper
{
    /**
     * Helps you download + install a single package in a synchronous way
     *
     * This executes all the required steps and waits for promises to complete
     *
     * @param Loop                  $loop        Loop instance which you can get from $composer->getLoop()
     * @param DownloaderInterface   $downloader  Downloader instance you can get from $composer->getDownloadManager()->getDownloader('zip') for example
     * @param string                $path        the installation path for the package
     * @param PackageInterface      $package     the package to install
     * @param PackageInterface|null $prevPackage the previous package if this is an update and not an initial installation
     *
     * @return void
     */
    public static function downloadAndInstallPackageSync(Loop $loop, DownloaderInterface $downloader, $path, PackageInterface $package, PackageInterface $prevPackage = null)
    {
        $type = $prevPackage ? 'update' : 'install';

        try {
            self::await($loop, $downloader->download($package, $path, $prevPackage));

            self::await($loop, $downloader->prepare($type, $package, $path, $prevPackage));

            if ($type === 'update') {
                self::await($loop, $downloader->update($package, $prevPackage, $path));
            } else {
                self::await($loop, $downloader->install($package, $path));
            }
        } catch (\Exception $e) {
            self::await($loop, $downloader->cleanup($type, $package, $path, $prevPackage));
            throw $e;
        }

        self::await($loop, $downloader->cleanup($type, $package, $path, $prevPackage));
    }

    /**
     * Waits for a promise to resolve
     *
     * @param Loop                  $loop    Loop instance which you can get from $composer->getLoop()
     * @param PromiseInterface|null $promise
     *
     * @return void
     */
    public static function await(Loop $loop, PromiseInterface $promise = null)
    {
        if ($promise) {
            $loop->wait(array($promise));
        }
    }
}
