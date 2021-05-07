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

namespace Composer\Downloader;

use Composer\Package\PackageInterface;

/**
 * Downloader for tar files: tar, tar.gz or tar.bz2
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class TarDownloader extends ArchiveDownloader
{
    /**
     * {@inheritDoc}
     */
    protected function extract(PackageInterface $package, $file, $path)
    {
        // Can throw an UnexpectedValueException
        $archive = new \PharData($file);
        $archive->extractTo($path, null, true);
    }
}
