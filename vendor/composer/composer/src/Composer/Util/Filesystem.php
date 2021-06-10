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

use React\Promise\PromiseInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Filesystem
{
    /** @var ?ProcessExecutor */
    private $processExecutor;

    public function __construct(ProcessExecutor $executor = null)
    {
        $this->processExecutor = $executor;
    }

    public function remove($file)
    {
        if (is_dir($file)) {
            return $this->removeDirectory($file);
        }

        if (file_exists($file)) {
            return $this->unlink($file);
        }

        return false;
    }

    /**
     * Checks if a directory is empty
     *
     * @param  string $dir
     * @return bool
     */
    public function isDirEmpty($dir)
    {
        $finder = Finder::create()
            ->ignoreVCS(false)
            ->ignoreDotFiles(false)
            ->depth(0)
            ->in($dir);

        return \count($finder) === 0;
    }

    public function emptyDirectory($dir, $ensureDirectoryExists = true)
    {
        if (is_link($dir) && file_exists($dir)) {
            $this->unlink($dir);
        }

        if ($ensureDirectoryExists) {
            $this->ensureDirectoryExists($dir);
        }

        if (is_dir($dir)) {
            $finder = Finder::create()
                ->ignoreVCS(false)
                ->ignoreDotFiles(false)
                ->depth(0)
                ->in($dir);

            foreach ($finder as $path) {
                $this->remove((string) $path);
            }
        }
    }

    /**
     * Recursively remove a directory
     *
     * Uses the process component if proc_open is enabled on the PHP
     * installation.
     *
     * @param  string            $directory
     * @throws \RuntimeException
     * @return bool
     */
    public function removeDirectory($directory)
    {
        $edgeCaseResult = $this->removeEdgeCases($directory);
        if ($edgeCaseResult !== null) {
            return $edgeCaseResult;
        }

        if (Platform::isWindows()) {
            $cmd = sprintf('rmdir /S /Q %s', ProcessExecutor::escape(realpath($directory)));
        } else {
            $cmd = sprintf('rm -rf %s', ProcessExecutor::escape($directory));
        }

        $result = $this->getProcess()->execute($cmd, $output) === 0;

        // clear stat cache because external processes aren't tracked by the php stat cache
        clearstatcache();

        if ($result && !is_dir($directory)) {
            return true;
        }

        return $this->removeDirectoryPhp($directory);
    }

    /**
     * Recursively remove a directory asynchronously
     *
     * Uses the process component if proc_open is enabled on the PHP
     * installation.
     *
     * @param  string            $directory
     * @throws \RuntimeException
     * @return PromiseInterface
     */
    public function removeDirectoryAsync($directory)
    {
        $edgeCaseResult = $this->removeEdgeCases($directory);
        if ($edgeCaseResult !== null) {
            return \React\Promise\resolve($edgeCaseResult);
        }

        if (Platform::isWindows()) {
            $cmd = sprintf('rmdir /S /Q %s', ProcessExecutor::escape(realpath($directory)));
        } else {
            $cmd = sprintf('rm -rf %s', ProcessExecutor::escape($directory));
        }

        $promise = $this->getProcess()->executeAsync($cmd);

        $self = $this;

        return $promise->then(function ($process) use ($directory, $self) {
            // clear stat cache because external processes aren't tracked by the php stat cache
            clearstatcache();

            if ($process->isSuccessful()) {
                if (!is_dir($directory)) {
                    return \React\Promise\resolve(true);
                }
            }

            return \React\Promise\resolve($self->removeDirectoryPhp($directory));
        });
    }

    /**
     * @param string $directory
     *
     * @return bool|null Returns null, when no edge case was hit. Otherwise a bool whether removal was successfull
     */
    private function removeEdgeCases($directory, $fallbackToPhp = true)
    {
        if ($this->isSymlinkedDirectory($directory)) {
            return $this->unlinkSymlinkedDirectory($directory);
        }

        if ($this->isJunction($directory)) {
            return $this->removeJunction($directory);
        }

        if (is_link($directory)) {
            return unlink($directory);
        }

        if (!is_dir($directory) || !file_exists($directory)) {
            return true;
        }

        if (preg_match('{^(?:[a-z]:)?[/\\\\]+$}i', $directory)) {
            throw new \RuntimeException('Aborting an attempted deletion of '.$directory.', this was probably not intended, if it is a real use case please report it.');
        }

        if (!\function_exists('proc_open') && $fallbackToPhp) {
            return $this->removeDirectoryPhp($directory);
        }

        return null;
    }

    /**
     * Recursively delete directory using PHP iterators.
     *
     * Uses a CHILD_FIRST RecursiveIteratorIterator to sort files
     * before directories, creating a single non-recursive loop
     * to delete files/directories in the correct order.
     *
     * @param  string $directory
     * @return bool
     */
    public function removeDirectoryPhp($directory)
    {
        $edgeCaseResult = $this->removeEdgeCases($directory, false);
        if ($edgeCaseResult !== null) {
            return $edgeCaseResult;
        }

        try {
            $it = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        } catch (\UnexpectedValueException $e) {
            // re-try once after clearing the stat cache if it failed as it
            // sometimes fails without apparent reason, see https://github.com/composer/composer/issues/4009
            clearstatcache();
            usleep(100000);
            if (!is_dir($directory)) {
                return true;
            }
            $it = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        }
        $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($ri as $file) {
            if ($file->isDir()) {
                $this->rmdir($file->getPathname());
            } else {
                $this->unlink($file->getPathname());
            }
        }

        // release locks on the directory, see https://github.com/composer/composer/issues/9945
        unset($ri, $it, $file);

        return $this->rmdir($directory);
    }

    public function ensureDirectoryExists($directory)
    {
        if (!is_dir($directory)) {
            if (file_exists($directory)) {
                throw new \RuntimeException(
                    $directory.' exists and is not a directory.'
                );
            }
            if (!@mkdir($directory, 0777, true)) {
                throw new \RuntimeException(
                    $directory.' does not exist and could not be created.'
                );
            }
        }
    }

    /**
     * Attempts to unlink a file and in case of failure retries after 350ms on windows
     *
     * @param  string            $path
     * @throws \RuntimeException
     * @return bool
     */
    public function unlink($path)
    {
        $unlinked = @$this->unlinkImplementation($path);
        if (!$unlinked) {
            // retry after a bit on windows since it tends to be touchy with mass removals
            if (Platform::isWindows()) {
                usleep(350000);
                $unlinked = @$this->unlinkImplementation($path);
            }

            if (!$unlinked) {
                $error = error_get_last();
                $message = 'Could not delete '.$path.': ' . @$error['message'];
                if (Platform::isWindows()) {
                    $message .= "\nThis can be due to an antivirus or the Windows Search Indexer locking the file while they are analyzed";
                }

                throw new \RuntimeException($message);
            }
        }

        return true;
    }

    /**
     * Attempts to rmdir a file and in case of failure retries after 350ms on windows
     *
     * @param  string            $path
     * @throws \RuntimeException
     * @return bool
     */
    public function rmdir($path)
    {
        $deleted = @rmdir($path);
        if (!$deleted) {
            // retry after a bit on windows since it tends to be touchy with mass removals
            if (Platform::isWindows()) {
                usleep(350000);
                $deleted = @rmdir($path);
            }

            if (!$deleted) {
                $error = error_get_last();
                $message = 'Could not delete '.$path.': ' . @$error['message'];
                if (Platform::isWindows()) {
                    $message .= "\nThis can be due to an antivirus or the Windows Search Indexer locking the file while they are analyzed";
                }

                throw new \RuntimeException($message);
            }
        }

        return true;
    }

    /**
     * Copy then delete is a non-atomic version of {@link rename}.
     *
     * Some systems can't rename and also don't have proc_open,
     * which requires this solution.
     *
     * @param string $source
     * @param string $target
     */
    public function copyThenRemove($source, $target)
    {
        $this->copy($source, $target);
        if (!is_dir($source)) {
            $this->unlink($source);

            return;
        }

        $this->removeDirectoryPhp($source);
    }

    /**
     * Copies a file or directory from $source to $target.
     *
     * @param  string $source
     * @param  string $target
     * @return bool
     */
    public function copy($source, $target)
    {
        if (!is_dir($source)) {
            return copy($source, $target);
        }

        $it = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::SELF_FIRST);
        $this->ensureDirectoryExists($target);

        $result = true;
        /** @var RecursiveDirectoryIterator $ri */
        foreach ($ri as $file) {
            $targetPath = $target . DIRECTORY_SEPARATOR . $ri->getSubPathname();
            if ($file->isDir()) {
                $this->ensureDirectoryExists($targetPath);
            } else {
                $result = $result && copy($file->getPathname(), $targetPath);
            }
        }

        return $result;
    }

    public function rename($source, $target)
    {
        if (true === @rename($source, $target)) {
            return;
        }

        if (!\function_exists('proc_open')) {
            $this->copyThenRemove($source, $target);

            return;
        }

        if (Platform::isWindows()) {
            // Try to copy & delete - this is a workaround for random "Access denied" errors.
            $command = sprintf('xcopy %s %s /E /I /Q /Y', ProcessExecutor::escape($source), ProcessExecutor::escape($target));
            $result = $this->getProcess()->execute($command, $output);

            // clear stat cache because external processes aren't tracked by the php stat cache
            clearstatcache();

            if (0 === $result) {
                $this->remove($source);

                return;
            }
        } else {
            // We do not use PHP's "rename" function here since it does not support
            // the case where $source, and $target are located on different partitions.
            $command = sprintf('mv %s %s', ProcessExecutor::escape($source), ProcessExecutor::escape($target));
            $result = $this->getProcess()->execute($command, $output);

            // clear stat cache because external processes aren't tracked by the php stat cache
            clearstatcache();

            if (0 === $result) {
                return;
            }
        }

        $this->copyThenRemove($source, $target);
    }

    /**
     * Returns the shortest path from $from to $to
     *
     * @param  string                    $from
     * @param  string                    $to
     * @param  bool                      $directories if true, the source/target are considered to be directories
     * @throws \InvalidArgumentException
     * @return string
     */
    public function findShortestPath($from, $to, $directories = false)
    {
        if (!$this->isAbsolutePath($from) || !$this->isAbsolutePath($to)) {
            throw new \InvalidArgumentException(sprintf('$from (%s) and $to (%s) must be absolute paths.', $from, $to));
        }

        $from = lcfirst($this->normalizePath($from));
        $to = lcfirst($this->normalizePath($to));

        if ($directories) {
            $from = rtrim($from, '/') . '/dummy_file';
        }

        if (\dirname($from) === \dirname($to)) {
            return './'.basename($to);
        }

        $commonPath = $to;
        while (strpos($from.'/', $commonPath.'/') !== 0 && '/' !== $commonPath && !preg_match('{^[a-z]:/?$}i', $commonPath)) {
            $commonPath = strtr(\dirname($commonPath), '\\', '/');
        }

        if (0 !== strpos($from, $commonPath) || '/' === $commonPath) {
            return $to;
        }

        $commonPath = rtrim($commonPath, '/') . '/';
        $sourcePathDepth = substr_count(substr($from, \strlen($commonPath)), '/');
        $commonPathCode = str_repeat('../', $sourcePathDepth);

        return ($commonPathCode . substr($to, \strlen($commonPath))) ?: './';
    }

    /**
     * Returns PHP code that, when executed in $from, will return the path to $to
     *
     * @param  string                    $from
     * @param  string                    $to
     * @param  bool                      $directories if true, the source/target are considered to be directories
     * @param  bool                      $staticCode
     * @throws \InvalidArgumentException
     * @return string
     */
    public function findShortestPathCode($from, $to, $directories = false, $staticCode = false)
    {
        if (!$this->isAbsolutePath($from) || !$this->isAbsolutePath($to)) {
            throw new \InvalidArgumentException(sprintf('$from (%s) and $to (%s) must be absolute paths.', $from, $to));
        }

        $from = lcfirst($this->normalizePath($from));
        $to = lcfirst($this->normalizePath($to));

        if ($from === $to) {
            return $directories ? '__DIR__' : '__FILE__';
        }

        $commonPath = $to;
        while (strpos($from.'/', $commonPath.'/') !== 0 && '/' !== $commonPath && !preg_match('{^[a-z]:/?$}i', $commonPath) && '.' !== $commonPath) {
            $commonPath = strtr(\dirname($commonPath), '\\', '/');
        }

        if (0 !== strpos($from, $commonPath) || '/' === $commonPath || '.' === $commonPath) {
            return var_export($to, true);
        }

        $commonPath = rtrim($commonPath, '/') . '/';
        if (strpos($to, $from.'/') === 0) {
            return '__DIR__ . '.var_export(substr($to, \strlen($from)), true);
        }
        $sourcePathDepth = substr_count(substr($from, \strlen($commonPath)), '/') + $directories;
        if ($staticCode) {
            $commonPathCode = "__DIR__ . '".str_repeat('/..', $sourcePathDepth)."'";
        } else {
            $commonPathCode = str_repeat('dirname(', $sourcePathDepth).'__DIR__'.str_repeat(')', $sourcePathDepth);
        }
        $relTarget = substr($to, \strlen($commonPath));

        return $commonPathCode . (\strlen($relTarget) ? '.' . var_export('/' . $relTarget, true) : '');
    }

    /**
     * Checks if the given path is absolute
     *
     * @param  string $path
     * @return bool
     */
    public function isAbsolutePath($path)
    {
        return strpos($path, '/') === 0 || substr($path, 1, 1) === ':' || strpos($path, '\\\\') === 0;
    }

    /**
     * Returns size of a file or directory specified by path. If a directory is
     * given, it's size will be computed recursively.
     *
     * @param  string            $path Path to the file or directory
     * @throws \RuntimeException
     * @return int
     */
    public function size($path)
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("$path does not exist.");
        }
        if (is_dir($path)) {
            return $this->directorySize($path);
        }

        return filesize($path);
    }

    /**
     * Normalize a path. This replaces backslashes with slashes, removes ending
     * slash and collapses redundant separators and up-level references.
     *
     * @param  string $path Path to the file or directory
     * @return string
     */
    public function normalizePath($path)
    {
        $parts = array();
        $path = strtr($path, '\\', '/');
        $prefix = '';
        $absolute = false;

        // extract a prefix being a protocol://, protocol:, protocol://drive: or simply drive:
        if (preg_match('{^( [0-9a-z]{2,}+: (?: // (?: [a-z]: )? )? | [a-z]: )}ix', $path, $match)) {
            $prefix = $match[1];
            $path = substr($path, \strlen($prefix));
        }

        if (strpos($path, '/') === 0) {
            $absolute = true;
            $path = substr($path, 1);
        }

        $up = false;
        foreach (explode('/', $path) as $chunk) {
            if ('..' === $chunk && ($absolute || $up)) {
                array_pop($parts);
                $up = !(empty($parts) || '..' === end($parts));
            } elseif ('.' !== $chunk && '' !== $chunk) {
                $parts[] = $chunk;
                $up = '..' !== $chunk;
            }
        }

        return $prefix.($absolute ? '/' : '').implode('/', $parts);
    }

    /**
     * Remove trailing slashes if present to avoid issues with symlinks
     *
     * And other possible unforeseen disasters, see https://github.com/composer/composer/pull/9422
     *
     * @param  string $path
     * @return string
     */
    public static function trimTrailingSlash($path)
    {
        if (!preg_match('{^[/\\\\]+$}', $path)) {
            $path = rtrim($path, '/\\');
        }

        return $path;
    }

    /**
     * Return if the given path is local
     *
     * @param  string $path
     * @return bool
     */
    public static function isLocalPath($path)
    {
        return (bool) preg_match('{^(file://(?!//)|/(?!/)|/?[a-z]:[\\\\/]|\.\.[\\\\/]|[a-z0-9_.-]+[\\\\/])}i', $path);
    }

    public static function getPlatformPath($path)
    {
        if (Platform::isWindows()) {
            $path = preg_replace('{^(?:file:///([a-z]):?/)}i', 'file://$1:/', $path);
        }

        return preg_replace('{^file://}i', '', $path);
    }

    /**
     * Cross-platform safe version of is_readable()
     *
     * This will also check for readability by reading the file as is_readable can not be trusted on network-mounts
     * and \\wsl$ paths. See https://github.com/composer/composer/issues/8231 and https://bugs.php.net/bug.php?id=68926
     *
     * @param  string $path
     * @return bool
     */
    public static function isReadable($path)
    {
        if (is_readable($path)) {
            return true;
        }

        if (is_file($path)) {
            return false !== Silencer::call('file_get_contents', $path, false, null, 0, 1);
        }

        if (is_dir($path)) {
            return false !== Silencer::call('opendir', $path);
        }

        // assume false otherwise
        return false;
    }

    protected function directorySize($directory)
    {
        $it = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);

        $size = 0;
        foreach ($ri as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * @return ProcessExecutor
     */
    protected function getProcess()
    {
        if (!$this->processExecutor) {
            $this->processExecutor = new ProcessExecutor();
        }

        return $this->processExecutor;
    }

    /**
     * delete symbolic link implementation (commonly known as "unlink()")
     *
     * symbolic links on windows which link to directories need rmdir instead of unlink
     *
     * @param string $path
     *
     * @return bool
     */
    private function unlinkImplementation($path)
    {
        if (Platform::isWindows() && is_dir($path) && is_link($path)) {
            return rmdir($path);
        }

        return unlink($path);
    }

    /**
     * Creates a relative symlink from $link to $target
     *
     * @param  string $target The path of the binary file to be symlinked
     * @param  string $link   The path where the symlink should be created
     * @return bool
     */
    public function relativeSymlink($target, $link)
    {
        if (!function_exists('symlink')) {
            return false;
        }

        $cwd = getcwd();

        $relativePath = $this->findShortestPath($link, $target);
        chdir(\dirname($link));
        $result = @symlink($relativePath, $link);

        chdir($cwd);

        return $result;
    }

    /**
     * return true if that directory is a symlink.
     *
     * @param string $directory
     *
     * @return bool
     */
    public function isSymlinkedDirectory($directory)
    {
        if (!is_dir($directory)) {
            return false;
        }

        $resolved = $this->resolveSymlinkedDirectorySymlink($directory);

        return is_link($resolved);
    }

    /**
     * @param string $directory
     *
     * @return bool
     */
    private function unlinkSymlinkedDirectory($directory)
    {
        $resolved = $this->resolveSymlinkedDirectorySymlink($directory);

        return $this->unlink($resolved);
    }

    /**
     * resolve pathname to symbolic link of a directory
     *
     * @param string $pathname directory path to resolve
     *
     * @return string resolved path to symbolic link or original pathname (unresolved)
     */
    private function resolveSymlinkedDirectorySymlink($pathname)
    {
        if (!is_dir($pathname)) {
            return $pathname;
        }

        $resolved = rtrim($pathname, '/');

        if (!\strlen($resolved)) {
            return $pathname;
        }

        return $resolved;
    }

    /**
     * Creates an NTFS junction.
     *
     * @param string $target
     * @param string $junction
     */
    public function junction($target, $junction)
    {
        if (!Platform::isWindows()) {
            throw new \LogicException(sprintf('Function %s is not available on non-Windows platform', __CLASS__));
        }
        if (!is_dir($target)) {
            throw new IOException(sprintf('Cannot junction to "%s" as it is not a directory.', $target), 0, null, $target);
        }
        $cmd = sprintf(
            'mklink /J %s %s',
            ProcessExecutor::escape(str_replace('/', DIRECTORY_SEPARATOR, $junction)),
            ProcessExecutor::escape(realpath($target))
        );
        if ($this->getProcess()->execute($cmd, $output) !== 0) {
            throw new IOException(sprintf('Failed to create junction to "%s" at "%s".', $target, $junction), 0, null, $target);
        }
        clearstatcache(true, $junction);
    }

    /**
     * Returns whether the target directory is a Windows NTFS Junction.
     *
     * We test if the path is a directory and not an ordinary link, then check
     * that the mode value returned from lstat (which gives the status of the
     * link itself) is not a directory, by replicating the POSIX S_ISDIR test.
     *
     * This logic works because PHP does not set the mode value for a junction,
     * since there is no universal file type flag for it. Unfortunately an
     * uninitialized variable in PHP prior to 7.2.16 and 7.3.3 may cause a
     * random value to be returned. See https://bugs.php.net/bug.php?id=77552
     *
     * If this random value passes the S_ISDIR test, then a junction will not be
     * detected and a recursive delete operation could lead to loss of data in
     * the target directory. Note that Windows rmdir can handle this situation
     * and will only delete the junction (from Windows 7 onwards).
     *
     * @param  string $junction Path to check.
     * @return bool
     */
    public function isJunction($junction)
    {
        if (!Platform::isWindows()) {
            return false;
        }

        // Important to clear all caches first
        clearstatcache(true, $junction);

        if (!is_dir($junction) || is_link($junction)) {
            return false;
        }

        $stat = lstat($junction);

        // S_ISDIR test (S_IFDIR is 0x4000, S_IFMT is 0xF000 bitmask)
        return $stat ? 0x4000 !== ($stat['mode'] & 0xF000) : false;
    }

    /**
     * Removes a Windows NTFS junction.
     *
     * @param  string $junction
     * @return bool
     */
    public function removeJunction($junction)
    {
        if (!Platform::isWindows()) {
            return false;
        }
        $junction = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $junction), DIRECTORY_SEPARATOR);
        if (!$this->isJunction($junction)) {
            throw new IOException(sprintf('%s is not a junction and thus cannot be removed as one', $junction));
        }

        return $this->rmdir($junction);
    }

    public function filePutContentsIfModified($path, $content)
    {
        $currentContent = @file_get_contents($path);
        if (!$currentContent || ($currentContent != $content)) {
            return file_put_contents($path, $content);
        }

        return 0;
    }

    /**
     * Copy file using stream_copy_to_stream to work around https://bugs.php.net/bug.php?id=6463
     *
     * @param string $source
     * @param string $target
     */
    public function safeCopy($source, $target)
    {
        if (!file_exists($target) || !file_exists($source) || !$this->filesAreEqual($source, $target)) {
            $source = fopen($source, 'r');
            $target = fopen($target, 'w+');

            stream_copy_to_stream($source, $target);
            fclose($source);
            fclose($target);
        }
    }

    /**
     * compare 2 files
     * https://stackoverflow.com/questions/3060125/can-i-use-file-get-contents-to-compare-two-files
     */
    private function filesAreEqual($a, $b)
    {
        // Check if filesize is different
        if (filesize($a) !== filesize($b)) {
            return false;
        }

        // Check if content is different
        $ah = fopen($a, 'rb');
        $bh = fopen($b, 'rb');

        $result = true;
        while (!feof($ah)) {
            if (fread($ah, 8192) != fread($bh, 8192)) {
                $result = false;
                break;
            }
        }

        fclose($ah);
        fclose($bh);

        return $result;
    }
}
