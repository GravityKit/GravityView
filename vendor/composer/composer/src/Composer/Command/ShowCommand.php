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

namespace Composer\Command;

use Composer\Composer;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\Filter\PlatformRequirementFilter\PlatformRequirementFilterFactory;
use Composer\Json\JsonFile;
use Composer\Package\BasePackage;
use Composer\Package\CompletePackageInterface;
use Composer\Package\Link;
use Composer\Package\AliasPackage;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Package\Version\VersionSelector;
use Composer\Pcre\Preg;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\ComposerRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\FilterRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\InstalledRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RepositorySet;
use Composer\Repository\RootPackageRepository;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Semver;
use Composer\Spdx\SpdxLicenses;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Robert Schönthal <seroscho@googlemail.com>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Jérémy Romey <jeremyFreeAgent>
 * @author Mihai Plasoianu <mihai@plasoianu.de>
 */
class ShowCommand extends BaseCommand
{
    /** @var VersionParser */
    protected $versionParser;
    /** @var string[] */
    protected $colors;

    /** @var ?RepositorySet */
    private $repositorySet;

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('show')
            ->setAliases(array('info'))
            ->setDescription('Shows information about packages.')
            ->setDefinition(array(
                new InputArgument('package', InputArgument::OPTIONAL, 'Package to inspect. Or a name including a wildcard (*) to filter lists of packages instead.'),
                new InputArgument('version', InputArgument::OPTIONAL, 'Version or version constraint to inspect'),
                new InputOption('all', null, InputOption::VALUE_NONE, 'List all packages'),
                new InputOption('locked', null, InputOption::VALUE_NONE, 'List all locked packages'),
                new InputOption('installed', 'i', InputOption::VALUE_NONE, 'List installed packages only (enabled by default, only present for BC).'),
                new InputOption('platform', 'p', InputOption::VALUE_NONE, 'List platform packages only'),
                new InputOption('available', 'a', InputOption::VALUE_NONE, 'List available packages only'),
                new InputOption('self', 's', InputOption::VALUE_NONE, 'Show the root package information'),
                new InputOption('name-only', 'N', InputOption::VALUE_NONE, 'List package names only'),
                new InputOption('path', 'P', InputOption::VALUE_NONE, 'Show package paths'),
                new InputOption('tree', 't', InputOption::VALUE_NONE, 'List the dependencies as a tree'),
                new InputOption('latest', 'l', InputOption::VALUE_NONE, 'Show the latest version'),
                new InputOption('outdated', 'o', InputOption::VALUE_NONE, 'Show the latest version but only for packages that are outdated'),
                new InputOption('ignore', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Ignore specified package(s). Use it with the --outdated option if you don\'t want to be informed about new versions of some packages.'),
                new InputOption('minor-only', 'm', InputOption::VALUE_NONE, 'Show only packages that have minor SemVer-compatible updates. Use with the --outdated option.'),
                new InputOption('direct', 'D', InputOption::VALUE_NONE, 'Shows only packages that are directly required by the root package'),
                new InputOption('strict', null, InputOption::VALUE_NONE, 'Return a non-zero exit code when there are outdated packages'),
                new InputOption('format', 'f', InputOption::VALUE_REQUIRED, 'Format of the output: text or json', 'text'),
                new InputOption('no-dev', null, InputOption::VALUE_NONE, 'Disables search in require-dev packages.'),
                new InputOption('ignore-platform-req', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Ignore a specific platform requirement (php & ext- packages). Use with the --outdated option'),
                new InputOption('ignore-platform-reqs', null, InputOption::VALUE_NONE, 'Ignore all platform requirements (php & ext- packages). Use with the --outdated option'),
            ))
            ->setHelp(
                <<<EOT
The show command displays detailed information about a package, or
lists all packages available.

Read more at https://getcomposer.org/doc/03-cli.md#show
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->versionParser = new VersionParser;
        if ($input->getOption('tree')) {
            $this->initStyles($output);
        }

        $composer = $this->getComposer(false);
        $io = $this->getIO();

        if ($input->getOption('installed')) {
            $io->writeError('<warning>You are using the deprecated option "installed". Only installed packages are shown by default now. The --all option can be used to show all packages.</warning>');
        }

        if ($input->getOption('outdated')) {
            $input->setOption('latest', true);
        } elseif ($input->getOption('ignore')) {
            $io->writeError('<warning>You are using the option "ignore" for action other than "outdated", it will be ignored.</warning>');
        }

        if ($input->getOption('direct') && ($input->getOption('all') || $input->getOption('available') || $input->getOption('platform'))) {
            $io->writeError('The --direct (-D) option is not usable in combination with --all, --platform (-p) or --available (-a)');

            return 1;
        }

        if ($input->getOption('tree') && ($input->getOption('all') || $input->getOption('available'))) {
            $io->writeError('The --tree (-t) option is not usable in combination with --all or --available (-a)');

            return 1;
        }

        if ($input->getOption('tree') && $input->getOption('latest')) {
            $io->writeError('The --tree (-t) option is not usable in combination with --latest (-l)');

            return 1;
        }

        if ($input->getOption('tree') && $input->getOption('path')) {
            $io->writeError('The --tree (-t) option is not usable in combination with --path (-P)');

            return 1;
        }

        $format = $input->getOption('format');
        if (!in_array($format, array('text', 'json'))) {
            $io->writeError(sprintf('Unsupported format "%s". See help for supported formats.', $format));

            return 1;
        }

        $ignorePlatformReqs = $input->getOption('ignore-platform-reqs') ?: ($input->getOption('ignore-platform-req') ?: false);

        // init repos
        $platformOverrides = array();
        if ($composer) {
            $platformOverrides = $composer->getConfig()->get('platform') ?: array();
        }
        $platformRepo = new PlatformRepository(array(), $platformOverrides);
        $lockedRepo = null;

        if ($input->getOption('self')) {
            $package = $this->getComposer()->getPackage();
            if ($input->getOption('name-only')) {
                $io->write($package->getName());

                return 0;
            }
            $repos = $installedRepo = new InstalledRepository(array(new RootPackageRepository($package)));
        } elseif ($input->getOption('platform')) {
            $repos = $installedRepo = new InstalledRepository(array($platformRepo));
        } elseif ($input->getOption('available')) {
            $installedRepo = new InstalledRepository(array($platformRepo));
            if ($composer) {
                $repos = new CompositeRepository($composer->getRepositoryManager()->getRepositories());
                $installedRepo->addRepository($composer->getRepositoryManager()->getLocalRepository());
            } else {
                $defaultRepos = RepositoryFactory::defaultRepos($io);
                $repos = new CompositeRepository($defaultRepos);
                $io->writeError('No composer.json found in the current directory, showing available packages from ' . implode(', ', array_keys($defaultRepos)));
            }
        } elseif ($input->getOption('all') && $composer) {
            $localRepo = $composer->getRepositoryManager()->getLocalRepository();
            $locker = $composer->getLocker();
            if ($locker->isLocked()) {
                $lockedRepo = $locker->getLockedRepository(true);
                $installedRepo = new InstalledRepository(array($lockedRepo, $localRepo, $platformRepo));
            } else {
                $installedRepo = new InstalledRepository(array($localRepo, $platformRepo));
            }
            $repos = new CompositeRepository(array_merge(array(new FilterRepository($installedRepo, array('canonical' => false))), $composer->getRepositoryManager()->getRepositories()));
        } elseif ($input->getOption('all')) {
            $defaultRepos = RepositoryFactory::defaultRepos($io);
            $io->writeError('No composer.json found in the current directory, showing available packages from ' . implode(', ', array_keys($defaultRepos)));
            $installedRepo = new InstalledRepository(array($platformRepo));
            $repos = new CompositeRepository(array_merge(array($installedRepo), $defaultRepos));
        } elseif ($input->getOption('locked')) {
            if (!$composer || !$composer->getLocker()->isLocked()) {
                throw new \UnexpectedValueException('A valid composer.json and composer.lock files is required to run this command with --locked');
            }
            $locker = $composer->getLocker();
            $lockedRepo = $locker->getLockedRepository(!$input->getOption('no-dev'));
            $repos = $installedRepo = new InstalledRepository(array($lockedRepo));
        } else {
            // --installed / default case
            if (!$composer) {
                $composer = $this->getComposer();
            }
            $rootPkg = $composer->getPackage();
            $repos = $installedRepo = new InstalledRepository(array($composer->getRepositoryManager()->getLocalRepository()));

            if ($input->getOption('no-dev')) {
                $packages = $this->filterRequiredPackages($installedRepo, $rootPkg);
                $repos = $installedRepo = new InstalledRepository(array(new InstalledArrayRepository(array_map(function ($pkg) {
                    return clone $pkg;
                }, $packages))));
            }

            if (!$installedRepo->getPackages() && ($rootPkg->getRequires() || $rootPkg->getDevRequires())) {
                $io->writeError('<warning>No dependencies installed. Try running composer install or update.</warning>');
            }
        }

        if ($composer) {
            $commandEvent = new CommandEvent(PluginEvents::COMMAND, 'show', $input, $output);
            $composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);
        }

        if ($input->getOption('latest') && null === $composer) {
            $io->writeError('No composer.json found in the current directory, disabling "latest" option');
            $input->setOption('latest', false);
        }

        $packageFilter = $input->getArgument('package');

        // show single package or single version
        if (($packageFilter && false === strpos($packageFilter, '*')) || !empty($package)) {
            if (empty($package)) {
                list($package, $versions) = $this->getPackage($installedRepo, $repos, $input->getArgument('package'), $input->getArgument('version'));

                if (empty($package)) {
                    $options = $input->getOptions();
                    if (!isset($options['working-dir']) || !file_exists('composer.json')) {
                        if (PlatformRepository::isPlatformPackage($input->getArgument('package')) && !$input->getOption('platform')) {
                            throw new \InvalidArgumentException('Package ' . $packageFilter . ' not found, try using --platform (-p) to show platform packages.');
                        }
                        throw new \InvalidArgumentException('Package ' . $packageFilter . ' not found');
                    }

                    $io->writeError('Package ' . $packageFilter . ' not found in ' . $options['working-dir'] . '/composer.json');

                    return 1;
                }
            } else {
                $versions = array($package->getPrettyVersion() => $package->getVersion());
            }

            $exitCode = 0;
            if ($input->getOption('tree')) {
                $arrayTree = $this->generatePackageTree($package, $installedRepo, $repos);

                if ('json' === $format) {
                    $io->write(JsonFile::encode(array('installed' => array($arrayTree))));
                } else {
                    $this->displayPackageTree(array($arrayTree));
                }
            } else {
                $latestPackage = null;
                if ($input->getOption('latest')) {
                    $latestPackage = $this->findLatestPackage($package, $composer, $platformRepo, $input->getOption('minor-only'), $ignorePlatformReqs);
                }
                if (
                    $input->getOption('outdated')
                    && $input->getOption('strict')
                    && $latestPackage
                    && $latestPackage->getFullPrettyVersion() !== $package->getFullPrettyVersion()
                    && (!$latestPackage instanceof CompletePackageInterface || !$latestPackage->isAbandoned())
                ) {
                    $exitCode = 1;
                }
                if ($input->getOption('path')) {
                    $io->write($package->getName(), false);
                    $io->write(' ' . strtok(realpath($composer->getInstallationManager()->getInstallPath($package)), "\r\n"));

                    return $exitCode;
                }

                if ('json' === $format) {
                    $this->printPackageInfoAsJson($package, $versions, $installedRepo, $latestPackage ?: null);
                } else {
                    $this->printPackageInfo($package, $versions, $installedRepo, $latestPackage ?: null);
                }
            }

            return $exitCode;
        }

        // show tree view if requested
        if ($input->getOption('tree')) {
            $rootRequires = $this->getRootRequires();
            $packages = $installedRepo->getPackages();
            usort($packages, function (BasePackage $a, BasePackage $b) {
                return strcmp((string) $a, (string) $b);
            });
            $arrayTree = array();
            foreach ($packages as $package) {
                if (in_array($package->getName(), $rootRequires, true)) {
                    $arrayTree[] = $this->generatePackageTree($package, $installedRepo, $repos);
                }
            }

            if ('json' === $format) {
                $io->write(JsonFile::encode(array('installed' => $arrayTree)));
            } else {
                $this->displayPackageTree($arrayTree);
            }

            return 0;
        }

        // list packages
        $packages = array();
        $packageFilterRegex = null;
        if (null !== $packageFilter) {
            $packageFilterRegex = '{^'.str_replace('\\*', '.*?', preg_quote($packageFilter)).'$}i';
        }

        $packageListFilter = array();
        if ($input->getOption('direct')) {
            $packageListFilter = $this->getRootRequires();
        }

        if ($input->getOption('path') && null === $composer) {
            $io->writeError('No composer.json found in the current directory, disabling "path" option');
            $input->setOption('path', false);
        }

        foreach ($repos->getRepositories() as $repo) {
            if ($repo === $platformRepo) {
                $type = 'platform';
            } elseif ($lockedRepo !== null && $repo === $lockedRepo) {
                $type = 'locked';
            } elseif ($repo === $installedRepo || in_array($repo, $installedRepo->getRepositories(), true)) {
                $type = 'installed';
            } else {
                $type = 'available';
            }
            if ($repo instanceof ComposerRepository) {
                foreach ($repo->getPackageNames($packageFilter) as $name) {
                    $packages[$type][$name] = $name;
                }
            } else {
                foreach ($repo->getPackages() as $package) {
                    if (!isset($packages[$type][$package->getName()])
                        || !is_object($packages[$type][$package->getName()])
                        || version_compare($packages[$type][$package->getName()]->getVersion(), $package->getVersion(), '<')
                    ) {
                        while ($package instanceof AliasPackage) {
                            $package = $package->getAliasOf();
                        }
                        if (!$packageFilterRegex || Preg::isMatch($packageFilterRegex, $package->getName())) {
                            if (!$packageListFilter || in_array($package->getName(), $packageListFilter, true)) {
                                $packages[$type][$package->getName()] = $package;
                            }
                        }
                    }
                }
                if ($repo === $platformRepo) {
                    foreach ($platformRepo->getDisabledPackages() as $name => $package) {
                        $packages[$type][$name] = $package;
                    }
                }
            }
        }

        $showAllTypes = $input->getOption('all');
        $showLatest = $input->getOption('latest');
        $showMinorOnly = $input->getOption('minor-only');
        $ignoredPackages = array_map('strtolower', $input->getOption('ignore'));
        $indent = $showAllTypes ? '  ' : '';
        /** @var PackageInterface[] $latestPackages */
        $latestPackages = array();
        $exitCode = 0;
        $viewData = array();
        $viewMetaData = array();
        foreach (array('platform' => true, 'locked' => true, 'available' => false, 'installed' => true) as $type => $showVersion) {
            if (isset($packages[$type])) {
                ksort($packages[$type]);

                $nameLength = $versionLength = $latestLength = 0;

                if ($showLatest && $showVersion) {
                    foreach ($packages[$type] as $package) {
                        if (is_object($package)) {
                            $latestPackage = $this->findLatestPackage($package, $composer, $platformRepo, $showMinorOnly, $ignorePlatformReqs);
                            if ($latestPackage === false) {
                                continue;
                            }

                            $latestPackages[$package->getPrettyName()] = $latestPackage;
                        }
                    }
                }

                $writePath = !$input->getOption('name-only') && $input->getOption('path');
                $writeVersion = !$input->getOption('name-only') && !$input->getOption('path') && $showVersion;
                $writeLatest = $writeVersion && $showLatest;
                $writeDescription = !$input->getOption('name-only') && !$input->getOption('path');

                $hasOutdatedPackages = false;

                $viewData[$type] = array();
                foreach ($packages[$type] as $package) {
                    $packageViewData = array();
                    if (is_object($package)) {
                        $latestPackage = null;
                        if ($showLatest && isset($latestPackages[$package->getPrettyName()])) {
                            $latestPackage = $latestPackages[$package->getPrettyName()];
                        }

                        // Determine if Composer is checking outdated dependencies and if current package should trigger non-default exit code
                        $packageIsUpToDate = $latestPackage && $latestPackage->getFullPrettyVersion() === $package->getFullPrettyVersion() && (!$latestPackage instanceof CompletePackageInterface || !$latestPackage->isAbandoned());
                        $packageIsIgnored = \in_array($package->getPrettyName(), $ignoredPackages, true);
                        if ($input->getOption('outdated') && ($packageIsUpToDate || $packageIsIgnored)) {
                            continue;
                        }

                        if ($input->getOption('outdated') || $input->getOption('strict')) {
                            $hasOutdatedPackages = true;
                        }

                        $packageViewData['name'] = $package->getPrettyName();
                        $nameLength = max($nameLength, strlen($package->getPrettyName()));
                        if ($writeVersion) {
                            $packageViewData['version'] = $package->getFullPrettyVersion();
                            $versionLength = max($versionLength, strlen($package->getFullPrettyVersion()));
                        }
                        if ($writeLatest && $latestPackage) {
                            $packageViewData['latest'] = $latestPackage->getFullPrettyVersion();
                            $packageViewData['latest-status'] = $this->getUpdateStatus($latestPackage, $package);
                            $latestLength = max($latestLength, strlen($latestPackage->getFullPrettyVersion()));
                        }
                        if ($writeDescription && $package instanceof CompletePackageInterface) {
                            $packageViewData['description'] = $package->getDescription();
                        }
                        if ($writePath) {
                            $packageViewData['path'] = strtok(realpath($composer->getInstallationManager()->getInstallPath($package)), "\r\n");
                        }

                        if ($latestPackage instanceof CompletePackageInterface && $latestPackage->isAbandoned()) {
                            $replacement = is_string($latestPackage->getReplacementPackage())
                                ? 'Use ' . $latestPackage->getReplacementPackage() . ' instead'
                                : 'No replacement was suggested';
                            $packageWarning = sprintf(
                                'Package %s is abandoned, you should avoid using it. %s.',
                                $package->getPrettyName(),
                                $replacement
                            );
                            $packageViewData['warning'] = $packageWarning;
                        }
                    } else {
                        $packageViewData['name'] = $package;
                        $nameLength = max($nameLength, strlen($package));
                    }
                    $viewData[$type][] = $packageViewData;
                }
                $viewMetaData[$type] = array(
                    'nameLength' => $nameLength,
                    'versionLength' => $versionLength,
                    'latestLength' => $latestLength,
                );
                if ($input->getOption('strict') && $hasOutdatedPackages) {
                    $exitCode = 1;
                    break;
                }
            }
        }

        if ('json' === $format) {
            $io->write(JsonFile::encode($viewData));
        } else {
            if ($input->getOption('latest') && array_filter($viewData)) {
                if (!$io->isDecorated()) {
                    $io->writeError('Legend:');
                    $io->writeError('! patch or minor release available - update recommended');
                    $io->writeError('~ major release available - update possible');
                    if (!$input->getOption('outdated')) {
                        $io->writeError('= up to date version');
                    }
                } else {
                    $io->writeError('<info>Color legend:</info>');
                    $io->writeError('- <highlight>patch or minor</highlight> release available - update recommended');
                    $io->writeError('- <comment>major</comment> release available - update possible');
                    if (!$input->getOption('outdated')) {
                        $io->writeError('- <info>up to date</info> version');
                    }
                }
            }

            $width = $this->getTerminalWidth();

            foreach ($viewData as $type => $packages) {
                $nameLength = $viewMetaData[$type]['nameLength'];
                $versionLength = $viewMetaData[$type]['versionLength'];
                $latestLength = $viewMetaData[$type]['latestLength'];

                $writeVersion = $nameLength + $versionLength + 3 <= $width;
                $writeLatest = $nameLength + $versionLength + $latestLength + 3 <= $width;
                $writeDescription = $nameLength + $versionLength + $latestLength + 24 <= $width;

                if ($writeLatest && !$io->isDecorated()) {
                    $latestLength += 2;
                }

                if ($showAllTypes) {
                    if ('available' === $type) {
                        $io->write('<comment>' . $type . '</comment>:');
                    } else {
                        $io->write('<info>' . $type . '</info>:');
                    }
                }

                foreach ($packages as $package) {
                    $io->write($indent . str_pad($package['name'], $nameLength, ' '), false);
                    if (isset($package['version']) && $writeVersion) {
                        $io->write(' ' . str_pad($package['version'], $versionLength, ' '), false);
                    }
                    if (isset($package['latest']) && $writeLatest) {
                        $latestVersion = $package['latest'];
                        $updateStatus = $package['latest-status'];
                        $style = $this->updateStatusToVersionStyle($updateStatus);
                        if (!$io->isDecorated()) {
                            $latestVersion = str_replace(array('up-to-date', 'semver-safe-update', 'update-possible'), array('=', '!', '~'), $updateStatus) . ' ' . $latestVersion;
                        }
                        $io->write(' <' . $style . '>' . str_pad($latestVersion, $latestLength, ' ') . '</' . $style . '>', false);
                    }
                    if (isset($package['description']) && $writeDescription) {
                        $description = strtok($package['description'], "\r\n");
                        $remaining = $width - $nameLength - $versionLength - 4;
                        if ($writeLatest) {
                            $remaining -= $latestLength;
                        }
                        if (strlen($description) > $remaining) {
                            $description = substr($description, 0, $remaining - 3) . '...';
                        }
                        $io->write(' ' . $description, false);
                    }
                    if (isset($package['path'])) {
                        $io->write(' ' . $package['path'], false);
                    }
                    $io->write('');
                    if (isset($package['warning'])) {
                        $io->write('<warning>' . $package['warning'] . '</warning>');
                    }
                }

                if ($showAllTypes) {
                    $io->write('');
                }
            }
        }

        return $exitCode;
    }

    /**
     * @return string[]
     */
    protected function getRootRequires()
    {
        $rootPackage = $this->getComposer()->getPackage();

        return array_map(
            'strtolower',
            array_keys(array_merge($rootPackage->getRequires(), $rootPackage->getDevRequires()))
        );
    }

    /**
     * @return array|string|string[]
     */
    protected function getVersionStyle(PackageInterface $latestPackage, PackageInterface $package)
    {
        return $this->updateStatusToVersionStyle($this->getUpdateStatus($latestPackage, $package));
    }

    /**
     * finds a package by name and version if provided
     *
     * @param  string                     $name
     * @param  ConstraintInterface|string $version
     * @throws \InvalidArgumentException
     * @return array{CompletePackageInterface|null, array<string, string>}
     */
    protected function getPackage(InstalledRepository $installedRepo, RepositoryInterface $repos, $name, $version = null)
    {
        $name = strtolower($name);
        $constraint = is_string($version) ? $this->versionParser->parseConstraints($version) : $version;

        $policy = new DefaultPolicy();
        $repositorySet = new RepositorySet('dev');
        $repositorySet->allowInstalledRepositories();
        $repositorySet->addRepository($repos);

        $matchedPackage = null;
        $versions = array();
        if (PlatformRepository::isPlatformPackage($name)) {
            $pool = $repositorySet->createPoolWithAllPackages();
        } else {
            $pool = $repositorySet->createPoolForPackage($name);
        }
        $matches = $pool->whatProvides($name, $constraint);
        foreach ($matches as $index => $package) {
            // avoid showing the 9999999-dev alias if the default branch has no branch-alias set
            if ($package instanceof AliasPackage && $package->getVersion() === VersionParser::DEFAULT_BRANCH_ALIAS) {
                $package = $package->getAliasOf();
            }

            // select an exact match if it is in the installed repo and no specific version was required
            if (null === $version && $installedRepo->hasPackage($package)) {
                $matchedPackage = $package;
            }

            $versions[$package->getPrettyVersion()] = $package->getVersion();
            $matches[$index] = $package->getId();
        }

        // select preferred package according to policy rules
        if (!$matchedPackage && $matches && $preferred = $policy->selectPreferredPackages($pool, $matches)) {
            $matchedPackage = $pool->literalToPackage($preferred[0]);
        }

        return array($matchedPackage, $versions);
    }

    /**
     * Prints package info.
     *
     * @param array<string, string>    $versions
     * @param PackageInterface|null    $latestPackage
     *
     * @return void
     */
    protected function printPackageInfo(CompletePackageInterface $package, array $versions, InstalledRepository $installedRepo, PackageInterface $latestPackage = null)
    {
        $io = $this->getIO();

        $this->printMeta($package, $versions, $installedRepo, $latestPackage ?: null);
        $this->printLinks($package, Link::TYPE_REQUIRE);
        $this->printLinks($package, Link::TYPE_DEV_REQUIRE, 'requires (dev)');

        if ($package->getSuggests()) {
            $io->write("\n<info>suggests</info>");
            foreach ($package->getSuggests() as $suggested => $reason) {
                $io->write($suggested . ' <comment>' . $reason . '</comment>');
            }
        }

        $this->printLinks($package, Link::TYPE_PROVIDE);
        $this->printLinks($package, Link::TYPE_CONFLICT);
        $this->printLinks($package, Link::TYPE_REPLACE);
    }

    /**
     * Prints package metadata.
     *
     * @param array<string, string>    $versions
     * @param PackageInterface|null    $latestPackage
     *
     * @return void
     */
    protected function printMeta(CompletePackageInterface $package, array $versions, InstalledRepository $installedRepo, PackageInterface $latestPackage = null)
    {
        $io = $this->getIO();
        $io->write('<info>name</info>     : ' . $package->getPrettyName());
        $io->write('<info>descrip.</info> : ' . $package->getDescription());
        $io->write('<info>keywords</info> : ' . implode(', ', $package->getKeywords() ?: array()));
        $this->printVersions($package, $versions, $installedRepo);
        if ($latestPackage) {
            $style = $this->getVersionStyle($latestPackage, $package);
            $io->write('<info>latest</info>   : <'.$style.'>' . $latestPackage->getPrettyVersion() . '</'.$style.'>');
        } else {
            $latestPackage = $package;
        }
        $io->write('<info>type</info>     : ' . $package->getType());
        $this->printLicenses($package);
        $io->write('<info>homepage</info> : ' . $package->getHomepage());
        $io->write('<info>source</info>   : ' . sprintf('[%s] <comment>%s</comment> %s', $package->getSourceType(), $package->getSourceUrl(), $package->getSourceReference()));
        $io->write('<info>dist</info>     : ' . sprintf('[%s] <comment>%s</comment> %s', $package->getDistType(), $package->getDistUrl(), $package->getDistReference()));
        if ($installedRepo->hasPackage($package)) {
            $io->write('<info>path</info>     : ' . sprintf('%s', realpath($this->getComposer()->getInstallationManager()->getInstallPath($package))));
        }
        $io->write('<info>names</info>    : ' . implode(', ', $package->getNames()));

        if ($latestPackage instanceof CompletePackageInterface && $latestPackage->isAbandoned()) {
            $replacement = ($latestPackage->getReplacementPackage() !== null)
                ? ' The author suggests using the ' . $latestPackage->getReplacementPackage(). ' package instead.'
                : null;

            $io->writeError(
                sprintf('<warning>Attention: This package is abandoned and no longer maintained.%s</warning>', $replacement)
            );
        }

        if ($package->getSupport()) {
            $io->write("\n<info>support</info>");
            foreach ($package->getSupport() as $type => $value) {
                $io->write('<comment>' . $type . '</comment> : '.$value);
            }
        }

        if ($package->getAutoload()) {
            $io->write("\n<info>autoload</info>");
            $autoloadConfig = $package->getAutoload();
            foreach ($autoloadConfig as $type => $autoloads) {
                $io->write('<comment>' . $type . '</comment>');

                if ($type === 'psr-0' || $type === 'psr-4') {
                    foreach ($autoloads as $name => $path) {
                        $io->write(($name ?: '*') . ' => ' . (is_array($path) ? implode(', ', $path) : ($path ?: '.')));
                    }
                } elseif ($type === 'classmap') {
                    $io->write(implode(', ', $autoloadConfig[$type]));
                }
            }
            if ($package->getIncludePaths()) {
                $io->write('<comment>include-path</comment>');
                $io->write(implode(', ', $package->getIncludePaths()));
            }
        }
    }

    /**
     * Prints all available versions of this package and highlights the installed one if any.
     *
     * @param array<string, string> $versions
     *
     * @return void
     */
    protected function printVersions(CompletePackageInterface $package, array $versions, InstalledRepository $installedRepo)
    {
        $versions = array_keys($versions);
        $versions = Semver::rsort($versions);

        // highlight installed version
        if ($installedPackages = $installedRepo->findPackages($package->getName())) {
            foreach ($installedPackages as $installedPackage) {
                $installedVersion = $installedPackage->getPrettyVersion();
                $key = array_search($installedVersion, $versions);
                if (false !== $key) {
                    $versions[$key] = '<info>* ' . $installedVersion . '</info>';
                }
            }
        }

        $versions = implode(', ', $versions);

        $this->getIO()->write('<info>versions</info> : ' . $versions);
    }

    /**
     * print link objects
     *
     * @param string                   $linkType
     * @param string                   $title
     *
     * @return void
     */
    protected function printLinks(CompletePackageInterface $package, $linkType, $title = null)
    {
        $title = $title ?: $linkType;
        $io = $this->getIO();
        if ($links = $package->{'get'.ucfirst($linkType)}()) {
            $io->write("\n<info>" . $title . "</info>");

            foreach ($links as $link) {
                $io->write($link->getTarget() . ' <comment>' . $link->getPrettyConstraint() . '</comment>');
            }
        }
    }

    /**
     * Prints the licenses of a package with metadata
     *
     * @return void
     */
    protected function printLicenses(CompletePackageInterface $package)
    {
        $spdxLicenses = new SpdxLicenses();

        $licenses = $package->getLicense();
        $io = $this->getIO();

        foreach ($licenses as $licenseId) {
            $license = $spdxLicenses->getLicenseByIdentifier($licenseId); // keys: 0 fullname, 1 osi, 2 url

            if (!$license) {
                $out = $licenseId;
            } else {
                // is license OSI approved?
                if ($license[1] === true) {
                    $out = sprintf('%s (%s) (OSI approved) %s', $license[0], $licenseId, $license[2]);
                } else {
                    $out = sprintf('%s (%s) %s', $license[0], $licenseId, $license[2]);
                }
            }

            $io->write('<info>license</info>  : ' . $out);
        }
    }

    /**
     * Prints package info in JSON format.
     *
     * @param array<string, string>    $versions
     *
     * @return void
     */
    protected function printPackageInfoAsJson(CompletePackageInterface $package, array $versions, InstalledRepository $installedRepo, PackageInterface $latestPackage = null)
    {
        $json = array(
            'name' => $package->getPrettyName(),
            'description' => $package->getDescription(),
            'keywords' => $package->getKeywords() ?: array(),
            'type' => $package->getType(),
            'homepage' => $package->getHomepage(),
            'names' => $package->getNames(),
        );

        $json = $this->appendVersions($json, $versions);
        $json = $this->appendLicenses($json, $package);

        if ($latestPackage) {
            $json['latest'] = $latestPackage->getPrettyVersion();
        } else {
            $latestPackage = $package;
        }

        if ($package->getSourceType()) {
            $json['source'] = array(
                'type' => $package->getSourceType(),
                'url' => $package->getSourceUrl(),
                'reference' => $package->getSourceReference(),
            );
        }

        if ($package->getDistType()) {
            $json['dist'] = array(
                'type' => $package->getDistType(),
                'url' => $package->getDistUrl(),
                'reference' => $package->getDistReference(),
            );
        }

        if ($installedRepo->hasPackage($package)) {
            $json['path'] = realpath($this->getComposer()->getInstallationManager()->getInstallPath($package));
            if ($json['path'] === false) {
                unset($json['path']);
            }
        }

        if ($latestPackage instanceof CompletePackageInterface && $latestPackage->isAbandoned()) {
            $json['replacement'] = $latestPackage->getReplacementPackage();
        }

        if ($package->getSuggests()) {
            $json['suggests'] = $package->getSuggests();
        }

        if ($package->getSupport()) {
            $json['support'] = $package->getSupport();
        }

        $json = $this->appendAutoload($json, $package);

        if ($package->getIncludePaths()) {
            $json['include_path'] = $package->getIncludePaths();
        }

        $json = $this->appendLinks($json, $package);

        $this->getIO()->write(JsonFile::encode($json));
    }

    /**
     * @param array<string, string|string[]|null> $json
     * @param array<string, string> $versions
     * @return array<string, string|string[]|null>
     */
    private function appendVersions($json, array $versions)
    {
        uasort($versions, 'version_compare');
        $versions = array_keys(array_reverse($versions));
        $json['versions'] = $versions;

        return $json;
    }

    /**
     * @param array<string, string|string[]|null> $json
     * @return array<string, string|string[]|null>
     */
    private function appendLicenses($json, CompletePackageInterface $package)
    {
        if ($licenses = $package->getLicense()) {
            $spdxLicenses = new SpdxLicenses();

            $json['licenses'] = array_map(function ($licenseId) use ($spdxLicenses) {
                $license = $spdxLicenses->getLicenseByIdentifier($licenseId); // keys: 0 fullname, 1 osi, 2 url

                if (!$license) {
                    return $licenseId;
                }

                return array(
                    'name' => $license[0],
                    'osi' => $licenseId,
                    'url' => $license[2],
                );
            }, $licenses);
        }

        return $json;
    }

    /**
     * @param array<string, string|string[]|null> $json
     * @return array<string, string|string[]|null>
     */
    private function appendAutoload($json, CompletePackageInterface $package)
    {
        if ($package->getAutoload()) {
            $autoload = array();

            foreach ($package->getAutoload() as $type => $autoloads) {
                if ($type === 'psr-0' || $type === 'psr-4') {
                    $psr = array();

                    foreach ($autoloads as $name => $path) {
                        if (!$path) {
                            $path = '.';
                        }

                        $psr[$name ?: '*'] = $path;
                    }

                    $autoload[$type] = $psr;
                } elseif ($type === 'classmap') {
                    $autoload['classmap'] = $autoloads;
                }
            }

            $json['autoload'] = $autoload;
        }

        return $json;
    }

    /**
     * @param array<string, string|string[]|null> $json
     * @return array<string, string|string[]|null>
     */
    private function appendLinks($json, CompletePackageInterface $package)
    {
        foreach (Link::$TYPES as $linkType) {
            $json = $this->appendLink($json, $package, $linkType);
        }

        return $json;
    }

    /**
     * @param array<string, string|string[]|null> $json
     * @param string $linkType
     * @return array<string, string|string[]|null>
     */
    private function appendLink($json, CompletePackageInterface $package, $linkType)
    {
        $links = $package->{'get' . ucfirst($linkType)}();

        if ($links) {
            $json[$linkType] = array();

            foreach ($links as $link) {
                $json[$linkType][$link->getTarget()] = $link->getPrettyConstraint();
            }
        }

        return $json;
    }

    /**
     * Init styles for tree
     *
     * @return void
     */
    protected function initStyles(OutputInterface $output)
    {
        $this->colors = array(
            'green',
            'yellow',
            'cyan',
            'magenta',
            'blue',
        );

        foreach ($this->colors as $color) {
            $style = new OutputFormatterStyle($color);
            $output->getFormatter()->setStyle($color, $style);
        }
    }

    /**
     * Display the tree
     *
     * @param array<int, array<string, string|mixed[]>> $arrayTree
     * @return void
     */
    protected function displayPackageTree(array $arrayTree)
    {
        $io = $this->getIO();
        foreach ($arrayTree as $package) {
            $io->write(sprintf('<info>%s</info>', $package['name']), false);
            $io->write(' ' . $package['version'], false);
            $io->write(' ' . strtok($package['description'], "\r\n"));

            if (isset($package['requires'])) {
                $requires = $package['requires'];
                $treeBar = '├';
                $j = 0;
                $total = count($requires);
                foreach ($requires as $require) {
                    $requireName = $require['name'];
                    $j++;
                    if ($j === $total) {
                        $treeBar = '└';
                    }
                    $level = 1;
                    $color = $this->colors[$level];
                    $info = sprintf(
                        '%s──<%s>%s</%s> %s',
                        $treeBar,
                        $color,
                        $requireName,
                        $color,
                        $require['version']
                    );
                    $this->writeTreeLine($info);

                    $treeBar = str_replace('└', ' ', $treeBar);
                    $packagesInTree = array($package['name'], $requireName);

                    $this->displayTree($require, $packagesInTree, $treeBar, $level + 1);
                }
            }
        }
    }

    /**
     * Generate the package tree
     *
     * @return array<string, array<int, array<string, mixed[]|string>>|string|null>
     */
    protected function generatePackageTree(
        PackageInterface $package,
        InstalledRepository $installedRepo,
        RepositoryInterface $remoteRepos
    ) {
        $requires = $package->getRequires();
        ksort($requires);
        $children = array();
        foreach ($requires as $requireName => $require) {
            $packagesInTree = array($package->getName(), $requireName);

            $treeChildDesc = array(
                'name' => $requireName,
                'version' => $require->getPrettyConstraint(),
            );

            $deepChildren = $this->addTree($requireName, $require, $installedRepo, $remoteRepos, $packagesInTree);

            if ($deepChildren) {
                $treeChildDesc['requires'] = $deepChildren;
            }

            $children[] = $treeChildDesc;
        }
        $tree = array(
            'name' => $package->getPrettyName(),
            'version' => $package->getPrettyVersion(),
            'description' => $package instanceof CompletePackageInterface ? $package->getDescription() : '',
        );

        if ($children) {
            $tree['requires'] = $children;
        }

        return $tree;
    }

    /**
     * Display a package tree
     *
     * @param array<string, array<int, array<string, mixed[]|string>>|string|null>|string $package
     * @param array<int, string|mixed[]> $packagesInTree
     * @param string $previousTreeBar
     * @param int $level
     *
     * @return void
     */
    protected function displayTree(
        $package,
        array $packagesInTree,
        $previousTreeBar = '├',
        $level = 1
    ) {
        $previousTreeBar = str_replace('├', '│', $previousTreeBar);
        if (is_array($package) && isset($package['requires'])) {
            $requires = $package['requires'];
            $treeBar = $previousTreeBar . '  ├';
            $i = 0;
            $total = count($requires);
            foreach ($requires as $require) {
                $currentTree = $packagesInTree;
                $i++;
                if ($i === $total) {
                    $treeBar = $previousTreeBar . '  └';
                }
                $colorIdent = $level % count($this->colors);
                $color = $this->colors[$colorIdent];

                $circularWarn = in_array(
                    $require['name'],
                    $currentTree,
                    true
                ) ? '(circular dependency aborted here)' : '';
                $info = rtrim(sprintf(
                    '%s──<%s>%s</%s> %s %s',
                    $treeBar,
                    $color,
                    $require['name'],
                    $color,
                    $require['version'],
                    $circularWarn
                ));
                $this->writeTreeLine($info);

                $treeBar = str_replace('└', ' ', $treeBar);

                $currentTree[] = $require['name'];
                $this->displayTree($require, $currentTree, $treeBar, $level + 1);
            }
        }
    }

    /**
     * Display a package tree
     *
     * @param  string   $name
     * @param  string[] $packagesInTree
     * @return array<int, array<string, array<int, array<string, string>>|string>>
     */
    protected function addTree(
        $name,
        Link $link,
        InstalledRepository $installedRepo,
        RepositoryInterface $remoteRepos,
        array $packagesInTree
    ) {
        $children = array();
        list($package) = $this->getPackage(
            $installedRepo,
            $remoteRepos,
            $name,
            $link->getPrettyConstraint() === 'self.version' ? $link->getConstraint() : $link->getPrettyConstraint()
        );
        if (is_object($package)) {
            $requires = $package->getRequires();
            ksort($requires);
            foreach ($requires as $requireName => $require) {
                $currentTree = $packagesInTree;

                $treeChildDesc = array(
                    'name' => $requireName,
                    'version' => $require->getPrettyConstraint(),
                );

                if (!in_array($requireName, $currentTree, true)) {
                    $currentTree[] = $requireName;
                    $deepChildren = $this->addTree($requireName, $require, $installedRepo, $remoteRepos, $currentTree);
                    if ($deepChildren) {
                        $treeChildDesc['requires'] = $deepChildren;
                    }
                }

                $children[] = $treeChildDesc;
            }
        }

        return $children;
    }

    /**
     * @param string $updateStatus
     * @return string
     */
    private function updateStatusToVersionStyle($updateStatus)
    {
        // 'up-to-date' is printed green
        // 'semver-safe-update' is printed red
        // 'update-possible' is printed yellow
        return str_replace(array('up-to-date', 'semver-safe-update', 'update-possible'), array('info', 'highlight', 'comment'), $updateStatus);
    }

    /**
     * @return string
     */
    private function getUpdateStatus(PackageInterface $latestPackage, PackageInterface $package)
    {
        if ($latestPackage->getFullPrettyVersion() === $package->getFullPrettyVersion()) {
            return 'up-to-date';
        }

        $constraint = $package->getVersion();
        if (0 !== strpos($constraint, 'dev-')) {
            $constraint = '^'.$constraint;
        }
        if ($latestPackage->getVersion() && Semver::satisfies($latestPackage->getVersion(), $constraint)) {
            // it needs an immediate semver-compliant upgrade
            return 'semver-safe-update';
        }

        // it needs an upgrade but has potential BC breaks so is not urgent
        return 'update-possible';
    }

    /**
     * @param string $line
     *
     * @return void
     */
    private function writeTreeLine($line)
    {
        $io = $this->getIO();
        if (!$io->isDecorated()) {
            $line = str_replace(array('└', '├', '──', '│'), array('`-', '|-', '-', '|'), $line);
        }

        $io->write($line);
    }

    /**
     * Given a package, this finds the latest package matching it
     *
     * @param bool $minorOnly
     * @param bool|string $ignorePlatformReqs
     *
     * @return PackageInterface|false
     */
    private function findLatestPackage(PackageInterface $package, Composer $composer, PlatformRepository $platformRepo, $minorOnly = false, $ignorePlatformReqs = false)
    {
        // find the latest version allowed in this repo set
        $name = $package->getName();
        $versionSelector = new VersionSelector($this->getRepositorySet($composer), $platformRepo);
        $stability = $composer->getPackage()->getMinimumStability();
        $flags = $composer->getPackage()->getStabilityFlags();
        if (isset($flags[$name])) {
            $stability = array_search($flags[$name], BasePackage::$stabilities, true);
        }

        $bestStability = $stability;
        if ($composer->getPackage()->getPreferStable()) {
            $bestStability = $package->getStability();
        }

        $targetVersion = null;
        if (0 === strpos($package->getVersion(), 'dev-')) {
            $targetVersion = $package->getVersion();
        }

        if ($targetVersion === null && $minorOnly) {
            $targetVersion = '^' . $package->getVersion();
        }

        $candidate = $versionSelector->findBestCandidate($name, $targetVersion, $bestStability, PlatformRequirementFilterFactory::fromBoolOrList($ignorePlatformReqs));
        while ($candidate instanceof AliasPackage) {
            $candidate = $candidate->getAliasOf();
        }

        return $candidate;
    }

    /**
     * @return RepositorySet
     */
    private function getRepositorySet(Composer $composer)
    {
        if (!$this->repositorySet) {
            $this->repositorySet = new RepositorySet($composer->getPackage()->getMinimumStability(), $composer->getPackage()->getStabilityFlags());
            $this->repositorySet->addRepository(new CompositeRepository($composer->getRepositoryManager()->getRepositories()));
        }

        return $this->repositorySet;
    }

    /**
     * Find package requires and child requires
     *
     * @param  array<PackageInterface> $bucket
     * @return array<PackageInterface>
     */
    private function filterRequiredPackages(RepositoryInterface $repo, PackageInterface $package, $bucket = array())
    {
        $requires = $package->getRequires();

        foreach ($repo->getPackages() as $candidate) {
            foreach ($candidate->getNames() as $name) {
                if (isset($requires[$name])) {
                    if (!in_array($candidate, $bucket, true)) {
                        $bucket[] = $candidate;
                        $bucket = $this->filterRequiredPackages($repo, $candidate, $bucket);
                    }
                    break;
                }
            }
        }

        return $bucket;
    }
}
