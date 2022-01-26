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

namespace Composer\DependencyResolver;

use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Package\AliasPackage;
use Composer\Package\BasePackage;
use Composer\Package\CompleteAliasPackage;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Package\Version\StabilityFilter;
use Composer\Pcre\Preg;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PrePoolCreateEvent;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Repository\RootPackageRepository;
use Composer\Semver\CompilingMatcher;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MatchAllConstraint;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Intervals;

/**
 * @author Nils Adermann <naderman@naderman.de>
 */
class PoolBuilder
{
    /**
     * @var int[]
     * @phpstan-var array<string, BasePackage::STABILITY_*>
     */
    private $acceptableStabilities;
    /**
     * @var int[]
     * @phpstan-var array<string, BasePackage::STABILITY_*>
     */
    private $stabilityFlags;
    /**
     * @var array[]
     * @phpstan-var array<string, array<string, array{alias: string, alias_normalized: string}>>
     */
    private $rootAliases;
    /**
     * @var string[]
     * @phpstan-var array<string, string>
     */
    private $rootReferences;
    /**
     * @var ?EventDispatcher
     */
    private $eventDispatcher;
    /**
     * @var PoolOptimizer|null
     */
    private $poolOptimizer;
    /**
     * @var IOInterface
     */
    private $io;
    /**
     * @var array[]
     * @phpstan-var array<string, AliasPackage[]>
     */
    private $aliasMap = array();
    /**
     * @var ConstraintInterface[]
     * @phpstan-var array<string, ConstraintInterface>
     */
    private $packagesToLoad = array();
    /**
     * @var ConstraintInterface[]
     * @phpstan-var array<string, ConstraintInterface>
     */
    private $loadedPackages = array();
    /**
     * @var array[]
     * @phpstan-var array<int, array<string, array<string, PackageInterface>>>
     */
    private $loadedPerRepo = array();
    /**
     * @var BasePackage[]
     */
    private $packages = array();
    /**
     * @var BasePackage[]
     */
    private $unacceptableFixedOrLockedPackages = array();
    /** @var string[] */
    private $updateAllowList = array();
    /** @var array<string, array<PackageInterface>> */
    private $skippedLoad = array();

    /**
     * Keeps a list of dependencies which are locked but were auto-unlocked as they are path repositories
     *
     * This half-unlocked state means the package itself will update but the UPDATE_LISTED_WITH_TRANSITIVE_DEPS*
     * flags will not apply until the package really gets unlocked in some other way than being a path repo
     *
     * @var array<string, true>
     */
    private $pathRepoUnlocked = array();

    /**
     * Keeps a list of dependencies which are root requirements, and as such
     * have already their maximum required range loaded and can not be
     * extended by markPackageNameForLoading
     *
     * Packages get cleared from this list if they get unlocked as in that case
     * we need to actually load them
     *
     * @var array<string, true>
     */
    private $maxExtendedReqs = array();
    /**
     * @var array
     * @phpstan-var array<string, bool>
     */
    private $updateAllowWarned = array();

    /** @var int */
    private $indexCounter = 0;

    /**
     * @param int[] $acceptableStabilities array of stability => BasePackage::STABILITY_* value
     * @phpstan-param array<string, BasePackage::STABILITY_*> $acceptableStabilities
     * @param int[] $stabilityFlags an array of package name => BasePackage::STABILITY_* value
     * @phpstan-param array<string, BasePackage::STABILITY_*> $stabilityFlags
     * @param array[] $rootAliases
     * @phpstan-param array<string, array<string, array{alias: string, alias_normalized: string}>> $rootAliases
     * @param string[] $rootReferences an array of package name => source reference
     * @phpstan-param array<string, string> $rootReferences
     */
    public function __construct(array $acceptableStabilities, array $stabilityFlags, array $rootAliases, array $rootReferences, IOInterface $io, EventDispatcher $eventDispatcher = null, PoolOptimizer $poolOptimizer = null)
    {
        $this->acceptableStabilities = $acceptableStabilities;
        $this->stabilityFlags = $stabilityFlags;
        $this->rootAliases = $rootAliases;
        $this->rootReferences = $rootReferences;
        $this->eventDispatcher = $eventDispatcher;
        $this->poolOptimizer = $poolOptimizer;
        $this->io = $io;
    }

    /**
     * @param RepositoryInterface[] $repositories
     * @return Pool
     */
    public function buildPool(array $repositories, Request $request)
    {
        if ($request->getUpdateAllowList()) {
            $this->updateAllowList = $request->getUpdateAllowList();
            $this->warnAboutNonMatchingUpdateAllowList($request);

            foreach ($request->getLockedRepository()->getPackages() as $lockedPackage) {
                if (!$this->isUpdateAllowed($lockedPackage)) {
                    // remember which packages we skipped loading remote content for in this partial update
                    $this->skippedLoad[$lockedPackage->getName()][] = $lockedPackage;
                    foreach ($lockedPackage->getReplaces() as $link) {
                        $this->skippedLoad[$link->getTarget()][] = $lockedPackage;
                    }

                    // Path repo packages are never loaded from lock, to force them to always remain in sync
                    // unless symlinking is disabled in which case we probably should rather treat them like
                    // regular packages. We mark them specially so they can be reloaded fully including update propagation
                    // if they do get unlocked, but by default they are unlocked without update propagation.
                    if ($lockedPackage->getDistType() === 'path') {
                        $transportOptions = $lockedPackage->getTransportOptions();
                        if (!isset($transportOptions['symlink']) || $transportOptions['symlink'] !== false) {
                            $this->pathRepoUnlocked[$lockedPackage->getName()] = true;
                            continue;
                        }
                    }

                    $request->lockPackage($lockedPackage);
                }
            }
        }

        foreach ($request->getFixedOrLockedPackages() as $package) {
            // using MatchAllConstraint here because fixed packages do not need to retrigger
            // loading any packages
            $this->loadedPackages[$package->getName()] = new MatchAllConstraint();

            // replace means conflict, so if a fixed package replaces a name, no need to load that one, packages would conflict anyways
            foreach ($package->getReplaces() as $link) {
                $this->loadedPackages[$link->getTarget()] = new MatchAllConstraint();
            }

            // TODO in how far can we do the above for conflicts? It's more tricky cause conflicts can be limited to
            // specific versions while replace is a conflict with all versions of the name

            if (
                $package->getRepository() instanceof RootPackageRepository
                || $package->getRepository() instanceof PlatformRepository
                || StabilityFilter::isPackageAcceptable($this->acceptableStabilities, $this->stabilityFlags, $package->getNames(), $package->getStability())
            ) {
                $this->loadPackage($request, $repositories, $package, false);
            } else {
                $this->unacceptableFixedOrLockedPackages[] = $package;
            }
        }

        foreach ($request->getRequires() as $packageName => $constraint) {
            // fixed and locked packages have already been added, so if a root require needs one of them, no need to do anything
            if (isset($this->loadedPackages[$packageName])) {
                continue;
            }

            $this->packagesToLoad[$packageName] = $constraint;
            $this->maxExtendedReqs[$packageName] = true;
        }

        // clean up packagesToLoad for anything we manually marked loaded above
        foreach ($this->packagesToLoad as $name => $constraint) {
            if (isset($this->loadedPackages[$name])) {
                unset($this->packagesToLoad[$name]);
            }
        }

        while (!empty($this->packagesToLoad)) {
            $this->loadPackagesMarkedForLoading($request, $repositories);
        }

        foreach ($this->packages as $i => $package) {
            // we check all alias related packages at once, so no need to check individual aliases
            // isset also checks non-null value
            if (!$package instanceof AliasPackage) {
                $constraint = new Constraint('==', $package->getVersion());
                $aliasedPackages = array($i => $package);
                if (isset($this->aliasMap[spl_object_hash($package)])) {
                    $aliasedPackages += $this->aliasMap[spl_object_hash($package)];
                }

                $found = false;
                foreach ($aliasedPackages as $packageOrAlias) {
                    if (CompilingMatcher::match($constraint, Constraint::OP_EQ, $packageOrAlias->getVersion())) {
                        $found = true;
                    }
                }
                if (!$found) {
                    foreach ($aliasedPackages as $index => $packageOrAlias) {
                        unset($this->packages[$index]);
                    }
                }
            }
        }

        if ($this->eventDispatcher) {
            $prePoolCreateEvent = new PrePoolCreateEvent(
                PluginEvents::PRE_POOL_CREATE,
                $repositories,
                $request,
                $this->acceptableStabilities,
                $this->stabilityFlags,
                $this->rootAliases,
                $this->rootReferences,
                $this->packages,
                $this->unacceptableFixedOrLockedPackages
            );
            $this->eventDispatcher->dispatch($prePoolCreateEvent->getName(), $prePoolCreateEvent);
            $this->packages = $prePoolCreateEvent->getPackages();
            $this->unacceptableFixedOrLockedPackages = $prePoolCreateEvent->getUnacceptableFixedPackages();
        }

        $pool = new Pool($this->packages, $this->unacceptableFixedOrLockedPackages);

        $this->aliasMap = array();
        $this->packagesToLoad = array();
        $this->loadedPackages = array();
        $this->loadedPerRepo = array();
        $this->packages = array();
        $this->unacceptableFixedOrLockedPackages = array();
        $this->maxExtendedReqs = array();
        $this->skippedLoad = array();
        $this->indexCounter = 0;

        $pool = $this->runOptimizer($request, $pool);

        Intervals::clear();

        return $pool;
    }

    /**
     * @param string $name
     * @return void
     */
    private function markPackageNameForLoading(Request $request, $name, ConstraintInterface $constraint)
    {
        // Skip platform requires at this stage
        if (PlatformRepository::isPlatformPackage($name)) {
            return;
        }

        // Root require (which was not unlocked) already loaded the maximum range so no
        // need to check anything here
        if (isset($this->maxExtendedReqs[$name])) {
            return;
        }

        // Root requires can not be overruled by dependencies so there is no point in
        // extending the loaded constraint for those.
        // This is triggered when loading a root require which was locked but got unlocked, then
        // we make sure that we load at most the intervals covered by the root constraint.
        $rootRequires = $request->getRequires();
        if (isset($rootRequires[$name]) && !Intervals::isSubsetOf($constraint, $rootRequires[$name])) {
            $constraint = $rootRequires[$name];
        }

        // Not yet loaded or already marked for a reload, set the constraint to be loaded
        if (!isset($this->loadedPackages[$name])) {
            // Maybe it was already marked before but not loaded yet. In that case
            // we have to extend the constraint (we don't check if they are identical because
            // MultiConstraint::create() will optimize anyway)
            if (isset($this->packagesToLoad[$name])) {
                // Already marked for loading and this does not expand the constraint to be loaded, nothing to do
                if (Intervals::isSubsetOf($constraint, $this->packagesToLoad[$name])) {
                    return;
                }

                // extend the constraint to be loaded
                $constraint = Intervals::compactConstraint(MultiConstraint::create(array($this->packagesToLoad[$name], $constraint), false));
            }

            $this->packagesToLoad[$name] = $constraint;

            return;
        }

        // No need to load this package with this constraint because it is
        // a subset of the constraint with which we have already loaded packages
        if (Intervals::isSubsetOf($constraint, $this->loadedPackages[$name])) {
            return;
        }

        // We have already loaded that package but not in the constraint that's
        // required. We extend the constraint and mark that package as not being loaded
        // yet so we get the required package versions
        $this->packagesToLoad[$name] = Intervals::compactConstraint(MultiConstraint::create(array($this->loadedPackages[$name], $constraint), false));
        unset($this->loadedPackages[$name]);
    }

    /**
     * @param RepositoryInterface[] $repositories
     * @return void
     */
    private function loadPackagesMarkedForLoading(Request $request, array $repositories)
    {
        foreach ($this->packagesToLoad as $name => $constraint) {
            $this->loadedPackages[$name] = $constraint;
        }

        $packageBatch = $this->packagesToLoad;
        $this->packagesToLoad = array();

        foreach ($repositories as $repoIndex => $repository) {
            if (empty($packageBatch)) {
                break;
            }

            // these repos have their packages fixed or locked if they need to be loaded so we
            // never need to load anything else from them
            if ($repository instanceof PlatformRepository || $repository === $request->getLockedRepository()) {
                continue;
            }
            $result = $repository->loadPackages($packageBatch, $this->acceptableStabilities, $this->stabilityFlags, isset($this->loadedPerRepo[$repoIndex]) ? $this->loadedPerRepo[$repoIndex] : array());

            foreach ($result['namesFound'] as $name) {
                // avoid loading the same package again from other repositories once it has been found
                unset($packageBatch[$name]);
            }
            foreach ($result['packages'] as $package) {
                $this->loadedPerRepo[$repoIndex][$package->getName()][$package->getVersion()] = $package;
                $this->loadPackage($request, $repositories, $package, !isset($this->pathRepoUnlocked[$package->getName()]));
            }
        }
    }

    /**
     * @param bool $propagateUpdate
     * @param RepositoryInterface[] $repositories
     * @return void
     */
    private function loadPackage(Request $request, array $repositories, BasePackage $package, $propagateUpdate)
    {
        $index = $this->indexCounter++;
        $this->packages[$index] = $package;

        if ($package instanceof AliasPackage) {
            $this->aliasMap[spl_object_hash($package->getAliasOf())][$index] = $package;
        }

        $name = $package->getName();

        // we're simply setting the root references on all versions for a name here and rely on the solver to pick the
        // right version. It'd be more work to figure out which versions and which aliases of those versions this may
        // apply to
        if (isset($this->rootReferences[$name])) {
            // do not modify the references on already locked or fixed packages
            if (!$request->isLockedPackage($package) && !$request->isFixedPackage($package)) {
                $package->setSourceDistReferences($this->rootReferences[$name]);
            }
        }

        // if propagateUpdate is false we are loading a fixed or locked package, root aliases do not apply as they are
        // manually loaded as separate packages in this case
        if ($propagateUpdate && isset($this->rootAliases[$name][$package->getVersion()])) {
            $alias = $this->rootAliases[$name][$package->getVersion()];
            if ($package instanceof AliasPackage) {
                $basePackage = $package->getAliasOf();
            } else {
                $basePackage = $package;
            }
            if ($basePackage instanceof CompletePackage) {
                $aliasPackage = new CompleteAliasPackage($basePackage, $alias['alias_normalized'], $alias['alias']);
            } else {
                $aliasPackage = new AliasPackage($basePackage, $alias['alias_normalized'], $alias['alias']);
            }
            $aliasPackage->setRootPackageAlias(true);

            $newIndex = $this->indexCounter++;
            $this->packages[$newIndex] = $aliasPackage;
            $this->aliasMap[spl_object_hash($aliasPackage->getAliasOf())][$newIndex] = $aliasPackage;
        }

        foreach ($package->getRequires() as $link) {
            $require = $link->getTarget();
            $linkConstraint = $link->getConstraint();

            // if the required package is loaded as a locked package only and hasn't had its deps analyzed
            if (isset($this->skippedLoad[$require])) {
                // if we're doing a full update or this is a partial update with transitive deps and we're currently
                // looking at a package which needs to be updated we need to unlock the package we now know is a
                // dependency of another package which we are trying to update, and then attempt to load it again
                if ($propagateUpdate && $request->getUpdateAllowTransitiveDependencies()) {
                    $skippedRootRequires = $this->getSkippedRootRequires($request, $require);

                    if ($request->getUpdateAllowTransitiveRootDependencies() || !$skippedRootRequires) {
                        $this->unlockPackage($request, $repositories, $require);
                        $this->markPackageNameForLoading($request, $require, $linkConstraint);
                    } else {
                        foreach ($skippedRootRequires as $rootRequire) {
                            if (!isset($this->updateAllowWarned[$rootRequire])) {
                                $this->updateAllowWarned[$rootRequire] = true;
                                $this->io->writeError('<warning>Dependency '.$rootRequire.' is also a root requirement. Package has not been listed as an update argument, so keeping locked at old version. Use --with-all-dependencies (-W) to include root dependencies.</warning>');
                            }
                        }
                    }
                } elseif (isset($this->pathRepoUnlocked[$require]) && !isset($this->loadedPackages[$require])) {
                    // if doing a partial update and a package depends on a path-repo-unlocked package which is not referenced by the root, we need to ensure it gets loaded as it was not loaded by the request's root requirements
                    // and would not be loaded above if update propagation is not allowed (which happens if the requirer is itself a path-repo-unlocked package) or if transitive deps are not allowed to be unlocked
                    $this->markPackageNameForLoading($request, $require, $linkConstraint);
                }
            } else {
                $this->markPackageNameForLoading($request, $require, $linkConstraint);
            }
        }

        // if we're doing a partial update with deps we also need to unlock packages which are being replaced in case
        // they are currently locked and thus prevent this updateable package from being installable/updateable
        if ($propagateUpdate && $request->getUpdateAllowTransitiveDependencies()) {
            foreach ($package->getReplaces() as $link) {
                $replace = $link->getTarget();
                if (isset($this->loadedPackages[$replace], $this->skippedLoad[$replace])) {
                    $skippedRootRequires = $this->getSkippedRootRequires($request, $replace);

                    if ($request->getUpdateAllowTransitiveRootDependencies() || !$skippedRootRequires) {
                        $this->unlockPackage($request, $repositories, $replace);
                        $this->markPackageNameForLoading($request, $replace, $link->getConstraint());
                    } else {
                        foreach ($skippedRootRequires as $rootRequire) {
                            if (!isset($this->updateAllowWarned[$rootRequire])) {
                                $this->updateAllowWarned[$rootRequire] = true;
                                $this->io->writeError('<warning>Dependency '.$rootRequire.' is also a root requirement. Package has not been listed as an update argument, so keeping locked at old version. Use --with-all-dependencies (-W) to include root dependencies.</warning>');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Checks if a particular name is required directly in the request
     *
     * @param string $name packageName
     * @return bool
     */
    private function isRootRequire(Request $request, $name)
    {
        $rootRequires = $request->getRequires();

        return isset($rootRequires[$name]);
    }

    /**
     * @param  string $name
     * @return string[]
     */
    private function getSkippedRootRequires(Request $request, $name)
    {
        if (!isset($this->skippedLoad[$name])) {
            return array();
        }

        $rootRequires = $request->getRequires();
        $matches = array();

        if (isset($rootRequires[$name])) {
            return array_map(function (PackageInterface $package) use ($name) {
                if ($name !== $package->getName()) {
                    return $package->getName() .' (via replace of '.$name.')';
                }

                return $package->getName();
            }, $this->skippedLoad[$name]);
        }

        foreach ($this->skippedLoad[$name] as $packageOrReplacer) {
            if (isset($rootRequires[$packageOrReplacer->getName()])) {
                $matches[] = $packageOrReplacer->getName();
            }
            foreach ($packageOrReplacer->getReplaces() as $link) {
                if (isset($rootRequires[$link->getTarget()])) {
                    if ($name !== $packageOrReplacer->getName()) {
                        $matches[] = $packageOrReplacer->getName() .' (via replace of '.$name.')';
                    } else {
                        $matches[] = $packageOrReplacer->getName();
                    }
                    break;
                }
            }
        }

        return $matches;
    }

    /**
     * Checks whether the update allow list allows this package in the lock file to be updated
     *
     * @return bool
     */
    private function isUpdateAllowed(BasePackage $package)
    {
        foreach ($this->updateAllowList as $pattern => $void) {
            $patternRegexp = BasePackage::packageNameToRegexp($pattern);
            if (Preg::isMatch($patternRegexp, $package->getName())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return void
     */
    private function warnAboutNonMatchingUpdateAllowList(Request $request)
    {
        foreach ($this->updateAllowList as $pattern => $void) {
            $patternRegexp = BasePackage::packageNameToRegexp($pattern);
            // update pattern matches a locked package? => all good
            foreach ($request->getLockedRepository()->getPackages() as $package) {
                if (Preg::isMatch($patternRegexp, $package->getName())) {
                    continue 2;
                }
            }
            // update pattern matches a root require? => all good, probably a new package
            foreach ($request->getRequires() as $packageName => $constraint) {
                if (Preg::isMatch($patternRegexp, $packageName)) {
                    continue 2;
                }
            }
            if (strpos($pattern, '*') !== false) {
                $this->io->writeError('<warning>Pattern "' . $pattern . '" listed for update does not match any locked packages.</warning>');
            } else {
                $this->io->writeError('<warning>Package "' . $pattern . '" listed for update is not locked.</warning>');
            }
        }
    }

    /**
     * Reverts the decision to use a locked package if a partial update with transitive dependencies
     * found that this package actually needs to be updated
     *
     * @param RepositoryInterface[] $repositories
     * @param string $name
     * @return void
     */
    private function unlockPackage(Request $request, array $repositories, $name)
    {
        foreach ($this->skippedLoad[$name] as $packageOrReplacer) {
            // if we unfixed a replaced package name, we also need to unfix the replacer itself
            // as long as it was not unfixed yet
            if ($packageOrReplacer->getName() !== $name && isset($this->skippedLoad[$packageOrReplacer->getName()])) {
                $replacerName = $packageOrReplacer->getName();
                if ($request->getUpdateAllowTransitiveRootDependencies() || (!$this->isRootRequire($request, $name) && !$this->isRootRequire($request, $replacerName))) {
                    $this->unlockPackage($request, $repositories, $replacerName);

                    if ($this->isRootRequire($request, $replacerName)) {
                        $this->markPackageNameForLoading($request, $replacerName, new MatchAllConstraint);
                    } else {
                        foreach ($this->packages as $loadedPackage) {
                            $requires = $loadedPackage->getRequires();
                            if (isset($requires[$replacerName])) {
                                $this->markPackageNameForLoading($request, $replacerName, $requires[$replacerName]->getConstraint());
                            }
                        }
                    }
                }
            }
        }

        if (isset($this->pathRepoUnlocked[$name])) {
            foreach ($this->packages as $index => $package) {
                if ($package->getName() === $name) {
                    $this->removeLoadedPackage($request, $repositories, $package, $index);
                }
            }
        }

        unset($this->skippedLoad[$name], $this->loadedPackages[$name], $this->maxExtendedReqs[$name], $this->pathRepoUnlocked[$name]);

        // remove locked package by this name which was already initialized
        foreach ($request->getLockedPackages() as $lockedPackage) {
            if (!($lockedPackage instanceof AliasPackage) && $lockedPackage->getName() === $name) {
                if (false !== $index = array_search($lockedPackage, $this->packages, true)) {
                    $request->unlockPackage($lockedPackage);
                    $this->removeLoadedPackage($request, $repositories, $lockedPackage, $index);

                    // make sure that any requirements for this package by other locked or fixed packages are now
                    // also loaded, as they were previously ignored because the locked (now unlocked) package already
                    // satisfied their requirements
                    foreach ($request->getFixedOrLockedPackages() as $fixedOrLockedPackage) {
                        if ($fixedOrLockedPackage === $lockedPackage) {
                            continue;
                        }

                        if (isset($this->skippedLoad[$fixedOrLockedPackage->getName()])) {
                            $requires = $fixedOrLockedPackage->getRequires();
                            if (isset($requires[$lockedPackage->getName()])) {
                                $this->markPackageNameForLoading($request, $lockedPackage->getName(), $requires[$lockedPackage->getName()]->getConstraint());
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param RepositoryInterface[] $repositories
     * @param int $index
     * @return void
     */
    private function removeLoadedPackage(Request $request, array $repositories, BasePackage $package, $index)
    {
        $repoIndex = array_search($package->getRepository(), $repositories, true);

        unset($this->loadedPerRepo[$repoIndex][$package->getName()][$package->getVersion()]);
        unset($this->packages[$index]);
        if (isset($this->aliasMap[spl_object_hash($package)])) {
            foreach ($this->aliasMap[spl_object_hash($package)] as $aliasIndex => $aliasPackage) {
                unset($this->loadedPerRepo[$repoIndex][$aliasPackage->getName()][$aliasPackage->getVersion()]);
                unset($this->packages[$aliasIndex]);
            }
            unset($this->aliasMap[spl_object_hash($package)]);
        }
    }

    /**
     * @return Pool
     */
    private function runOptimizer(Request $request, Pool $pool)
    {
        if (null === $this->poolOptimizer) {
            return $pool;
        }

        $total = \count($pool->getPackages());

        $pool = $this->poolOptimizer->optimize($request, $pool);

        $filtered = $total - \count($pool->getPackages());

        if (0 === $filtered) {
            return $pool;
        }

        $this->io->write(sprintf(
            '<info>Found %s package versions referenced in your dependency graph. %s (%d%%) were optimized away.</info>',
            number_format($total),
            number_format($filtered),
            round(100/$total*$filtered)
        ), true, IOInterface::VERY_VERBOSE);

        return $pool;
    }
}
