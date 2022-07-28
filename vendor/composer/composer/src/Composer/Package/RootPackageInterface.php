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

namespace Composer\Package;

/**
 * Defines additional fields that are only needed for the root package
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 *
 * @phpstan-import-type AutoloadRules from PackageInterface
 * @phpstan-import-type DevAutoloadRules from PackageInterface
 */
interface RootPackageInterface extends CompletePackageInterface
{
    /**
     * Returns a set of package names and their aliases
     *
     * @return array<array{package: string, version: string, alias: string, alias_normalized: string}>
     */
    public function getAliases();

    /**
     * Returns the minimum stability of the package
     *
     * @return string
     */
    public function getMinimumStability();

    /**
     * Returns the stability flags to apply to dependencies
     *
     * array('foo/bar' => 'dev')
     *
     * @return array<string, BasePackage::STABILITY_*>
     */
    public function getStabilityFlags();

    /**
     * Returns a set of package names and source references that must be enforced on them
     *
     * array('foo/bar' => 'abcd1234')
     *
     * @return array<string, string>
     */
    public function getReferences();

    /**
     * Returns true if the root package prefers picking stable packages over unstable ones
     *
     * @return bool
     */
    public function getPreferStable();

    /**
     * Returns the root package's configuration
     *
     * @return mixed[]
     */
    public function getConfig();

    /**
     * Set the required packages
     *
     * @param Link[] $requires A set of package links
     *
     * @return void
     */
    public function setRequires(array $requires);

    /**
     * Set the recommended packages
     *
     * @param Link[] $devRequires A set of package links
     *
     * @return void
     */
    public function setDevRequires(array $devRequires);

    /**
     * Set the conflicting packages
     *
     * @param Link[] $conflicts A set of package links
     *
     * @return void
     */
    public function setConflicts(array $conflicts);

    /**
     * Set the provided virtual packages
     *
     * @param Link[] $provides A set of package links
     *
     * @return void
     */
    public function setProvides(array $provides);

    /**
     * Set the packages this one replaces
     *
     * @param Link[] $replaces A set of package links
     *
     * @return void
     */
    public function setReplaces(array $replaces);

    /**
     * Set the autoload mapping
     *
     * @param array $autoload Mapping of autoloading rules
     * @phpstan-param AutoloadRules $autoload
     *
     * @return void
     */
    public function setAutoload(array $autoload);

    /**
     * Set the dev autoload mapping
     *
     * @param array $devAutoload Mapping of dev autoloading rules
     * @phpstan-param DevAutoloadRules $devAutoload
     *
     * @return void
     */
    public function setDevAutoload(array $devAutoload);

    /**
     * Set the stabilityFlags
     *
     * @param array<string, BasePackage::STABILITY_*> $stabilityFlags
     *
     * @return void
     */
    public function setStabilityFlags(array $stabilityFlags);

    /**
     * Set the minimumStability
     *
     * @param string $minimumStability
     *
     * @return void
     */
    public function setMinimumStability($minimumStability);

    /**
     * Set the preferStable
     *
     * @param bool $preferStable
     *
     * @return void
     */
    public function setPreferStable($preferStable);

    /**
     * Set the config
     *
     * @param mixed[] $config
     *
     * @return void
     */
    public function setConfig(array $config);

    /**
     * Set the references
     *
     * @param array<string, string> $references
     *
     * @return void
     */
    public function setReferences(array $references);

    /**
     * Set the aliases
     *
     * @param array<array{package: string, version: string, alias: string, alias_normalized: string}> $aliases
     *
     * @return void
     */
    public function setAliases(array $aliases);

    /**
     * Set the suggested packages
     *
     * @param array<string, string> $suggests A set of package names/comments
     *
     * @return void
     */
    public function setSuggests(array $suggests);

    /**
     * @param mixed[] $extra
     *
     * @return void
     */
    public function setExtra(array $extra);
}
