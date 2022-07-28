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

namespace Composer\Package\Archiver;

use Composer\Pcre\Preg;
use Composer\Util\Filesystem;
use FilesystemIterator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * A Symfony Finder wrapper which locates files that should go into archives
 *
 * Handles .gitignore, .gitattributes and .hgignore files as well as composer's
 * own exclude rules from composer.json
 *
 * @author Nils Adermann <naderman@naderman.de>
 */
class ArchivableFilesFinder extends \FilterIterator
{
    /**
     * @var Finder
     */
    protected $finder;

    /**
     * Initializes the internal Symfony Finder with appropriate filters
     *
     * @param string $sources Path to source files to be archived
     * @param string[] $excludes Composer's own exclude rules from composer.json
     * @param bool $ignoreFilters Ignore filters when looking for files
     */
    public function __construct($sources, array $excludes, $ignoreFilters = false)
    {
        $fs = new Filesystem();

        $sources = $fs->normalizePath(realpath($sources));

        if ($ignoreFilters) {
            $filters = array();
        } else {
            $filters = array(
                new GitExcludeFilter($sources),
                new ComposerExcludeFilter($sources, $excludes),
            );
        }

        $this->finder = new Finder();

        $filter = function (\SplFileInfo $file) use ($sources, $filters, $fs) {
            if ($file->isLink() && strpos($file->getRealPath(), $sources) !== 0) {
                return false;
            }

            $relativePath = Preg::replace(
                '#^'.preg_quote($sources, '#').'#',
                '',
                $fs->normalizePath($file->getRealPath())
            );

            $exclude = false;
            foreach ($filters as $filter) {
                $exclude = $filter->filter($relativePath, $exclude);
            }

            return !$exclude;
        };

        if (method_exists($filter, 'bindTo')) {
            $filter = $filter->bindTo(null);
        }

        $this->finder
            ->in($sources)
            ->filter($filter)
            ->ignoreVCS(true)
            ->ignoreDotFiles(false)
            ->sortByName();

        parent::__construct($this->finder->getIterator());
    }

    #[\ReturnTypeWillChange]
    public function accept()
    {
        /** @var SplFileInfo $current */
        $current = $this->getInnerIterator()->current();

        if (!$current->isDir()) {
            return true;
        }

        $iterator = new FilesystemIterator($current, FilesystemIterator::SKIP_DOTS);

        return !$iterator->valid();
    }
}
