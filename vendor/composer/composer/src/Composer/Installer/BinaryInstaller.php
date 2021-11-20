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

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Util\Platform;
use Composer\Util\ProcessExecutor;
use Composer\Util\Silencer;

/**
 * Utility to handle installation of package "bin"/binaries
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author Helmut Hummel <info@helhum.io>
 */
class BinaryInstaller
{
    /** @var string */
    protected $binDir;
    /** @var string */
    protected $binCompat;
    /** @var IOInterface */
    protected $io;
    /** @var Filesystem */
    protected $filesystem;

    /**
     * @param IOInterface $io
     * @param string      $binDir
     * @param string      $binCompat
     * @param Filesystem  $filesystem
     */
    public function __construct(IOInterface $io, $binDir, $binCompat, Filesystem $filesystem = null)
    {
        $this->binDir = $binDir;
        $this->binCompat = $binCompat;
        $this->io = $io;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function installBinaries(PackageInterface $package, $installPath, $warnOnOverwrite = true)
    {
        $binaries = $this->getBinaries($package);
        if (!$binaries) {
            return;
        }

        Platform::workaroundFilesystemIssues();

        foreach ($binaries as $bin) {
            $binPath = $installPath.'/'.$bin;
            if (!file_exists($binPath)) {
                $this->io->writeError('    <warning>Skipped installation of bin '.$bin.' for package '.$package->getName().': file not found in package</warning>');
                continue;
            }

            // in case a custom installer returned a relative path for the
            // $package, we can now safely turn it into a absolute path (as we
            // already checked the binary's existence). The following helpers
            // will require absolute paths to work properly.
            $binPath = realpath($binPath);

            $this->initializeBinDir();
            $link = $this->binDir.'/'.basename($bin);
            if (file_exists($link)) {
                if (is_link($link)) {
                    // likely leftover from a previous install, make sure
                    // that the target is still executable in case this
                    // is a fresh install of the vendor.
                    Silencer::call('chmod', $link, 0777 & ~umask());
                }
                if ($warnOnOverwrite) {
                    $this->io->writeError('    Skipped installation of bin '.$bin.' for package '.$package->getName().': name conflicts with an existing file');
                }
                continue;
            }

            if ($this->binCompat === "auto") {
                if (Platform::isWindows() || Platform::isWindowsSubsystemForLinux()) {
                    $this->installFullBinaries($binPath, $link, $bin, $package);
                } else {
                    $this->installSymlinkBinaries($binPath, $link);
                }
            } elseif ($this->binCompat === "full") {
                $this->installFullBinaries($binPath, $link, $bin, $package);
            } elseif ($this->binCompat === "symlink") {
                $this->installSymlinkBinaries($binPath, $link);
            }
            Silencer::call('chmod', $binPath, 0777 & ~umask());
        }
    }

    public function removeBinaries(PackageInterface $package)
    {
        $this->initializeBinDir();

        $binaries = $this->getBinaries($package);
        if (!$binaries) {
            return;
        }
        foreach ($binaries as $bin) {
            $link = $this->binDir.'/'.basename($bin);
            if (is_link($link) || file_exists($link)) {
                $this->filesystem->unlink($link);
            }
            if (file_exists($link.'.bat')) {
                $this->filesystem->unlink($link.'.bat');
            }
        }

        // attempt removing the bin dir in case it is left empty
        if (is_dir($this->binDir) && $this->filesystem->isDirEmpty($this->binDir)) {
            Silencer::call('rmdir', $this->binDir);
        }
    }

    public static function determineBinaryCaller($bin)
    {
        if ('.bat' === substr($bin, -4) || '.exe' === substr($bin, -4)) {
            return 'call';
        }

        $handle = fopen($bin, 'r');
        $line = fgets($handle);
        fclose($handle);
        if (preg_match('{^#!/(?:usr/bin/env )?(?:[^/]+/)*(.+)$}m', $line, $match)) {
            return trim($match[1]);
        }

        return 'php';
    }

    protected function getBinaries(PackageInterface $package)
    {
        return $package->getBinaries();
    }

    protected function installFullBinaries($binPath, $link, $bin, PackageInterface $package)
    {
        // add unixy support for cygwin and similar environments
        if ('.bat' !== substr($binPath, -4)) {
            $this->installUnixyProxyBinaries($binPath, $link);
            $link .= '.bat';
            if (file_exists($link)) {
                $this->io->writeError('    Skipped installation of bin '.$bin.'.bat proxy for package '.$package->getName().': a .bat proxy was already installed');
            }
        }
        if (!file_exists($link)) {
            file_put_contents($link, $this->generateWindowsProxyCode($binPath, $link));
            Silencer::call('chmod', $link, 0777 & ~umask());
        }
    }

    protected function installSymlinkBinaries($binPath, $link)
    {
        if (!$this->filesystem->relativeSymlink($binPath, $link)) {
            $this->installUnixyProxyBinaries($binPath, $link);
        }
    }

    protected function installUnixyProxyBinaries($binPath, $link)
    {
        file_put_contents($link, $this->generateUnixyProxyCode($binPath, $link));
        Silencer::call('chmod', $link, 0777 & ~umask());
    }

    protected function initializeBinDir()
    {
        $this->filesystem->ensureDirectoryExists($this->binDir);
        $this->binDir = realpath($this->binDir);
    }

    protected function generateWindowsProxyCode($bin, $link)
    {
        $binPath = $this->filesystem->findShortestPath($link, $bin);
        $caller = self::determineBinaryCaller($bin);

        return "@ECHO OFF\r\n".
            "setlocal DISABLEDELAYEDEXPANSION\r\n".
            "SET BIN_TARGET=%~dp0/".trim(ProcessExecutor::escape($binPath), '"\'')."\r\n".
            "{$caller} \"%BIN_TARGET%\" %*\r\n";
    }

    protected function generateUnixyProxyCode($bin, $link)
    {
        $binPath = $this->filesystem->findShortestPath($link, $bin);

        $binDir = ProcessExecutor::escape(dirname($binPath));
        $binFile = basename($binPath);

        $binContents = file_get_contents($bin);
        // For php files, we generate a PHP proxy instead of a shell one,
        // which allows calling the proxy with a custom php process
        if (preg_match('{^(?:#!(?:/usr)?/bin/env php|#!(?:/usr)?/bin/php|<?php)\r?\n}', $binContents, $match)) {
            // verify the file is not a phar file, because those do not support php-proxying
            if (false === ($pos = strpos($binContents, '__HALT_COMPILER')) || false === strpos(substr($binContents, 0, $pos), 'Phar::mapPhar')) {
                $proxyCode = trim($match[0]);
                // carry over the existing shebang if present, otherwise add our own
                if ($proxyCode === "<?php") {
                    $proxyCode = "#!/usr/bin/env php";
                }
                $binPathExported = var_export($binPath, true);

                return $proxyCode . "\n" . <<<PROXY
<?php

/**
 * Proxy PHP file generated by Composer
 *
 * This file includes the referenced bin path ($binPath) using eval to remove the shebang if present
 *
 * @generated
 */

\$binPath = realpath(__DIR__ . "/" . $binPathExported);
\$contents = file_get_contents(\$binPath);
\$contents = preg_replace('{^#!/.+\\r?\\n<\\?(php)?}', '', \$contents, 1, \$replaced);
if (\$replaced) {
    \$contents = strtr(\$contents, array(
        '__FILE__' => var_export(\$binPath, true),
        '__DIR__' => var_export(dirname(\$binPath), true),
    ));

    eval(\$contents);
    exit(0);
}
include \$binPath;

PROXY;
            }
        }

        $proxyCode = <<<PROXY
#!/usr/bin/env sh

dir=\$(cd "\${0%[/\\\\]*}" > /dev/null; cd $binDir && pwd)

if [ -d /proc/cygdrive ]; then
    case \$(which php) in
        \$(readlink -n /proc/cygdrive)/*)
            # We are in Cygwin using Windows php, so the path must be translated
            dir=\$(cygpath -m "\$dir");
            ;;
    esac
fi

"\${dir}/$binFile" "\$@"

PROXY;

        return $proxyCode;
    }
}
