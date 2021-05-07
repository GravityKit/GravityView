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

namespace Composer\Package\Version;

use Composer\Repository\PlatformRepository;
use Composer\Semver\VersionParser as SemverVersionParser;
use Composer\Semver\Semver;

class VersionParser extends SemverVersionParser
{
    const DEFAULT_BRANCH_ALIAS = '9999999-dev';

    private static $constraints = array();

    /**
     * {@inheritDoc}
     */
    public function parseConstraints($constraints)
    {
        if (!isset(self::$constraints[$constraints])) {
            self::$constraints[$constraints] = parent::parseConstraints($constraints);
        }

        return self::$constraints[$constraints];
    }

    /**
     * Parses an array of strings representing package/version pairs.
     *
     * The parsing results in an array of arrays, each of which
     * contain a 'name' key with value and optionally a 'version' key with value.
     *
     * @param array $pairs a set of package/version pairs separated by ":", "=" or " "
     *
     * @return array[] array of arrays containing a name and (if provided) a version
     */
    public function parseNameVersionPairs(array $pairs)
    {
        $pairs = array_values($pairs);
        $result = array();

        for ($i = 0, $count = count($pairs); $i < $count; $i++) {
            $pair = preg_replace('{^([^=: ]+)[=: ](.*)$}', '$1 $2', trim($pairs[$i]));
            if (false === strpos($pair, ' ') && isset($pairs[$i + 1]) && false === strpos($pairs[$i + 1], '/') && !PlatformRepository::isPlatformPackage($pairs[$i + 1])) {
                $pair .= ' '.$pairs[$i + 1];
                $i++;
            }

            if (strpos($pair, ' ')) {
                list($name, $version) = explode(' ', $pair, 2);
                $result[] = array('name' => $name, 'version' => $version);
            } else {
                $result[] = array('name' => $pair);
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public static function isUpgrade($normalizedFrom, $normalizedTo)
    {
        if ($normalizedFrom === $normalizedTo) {
            return true;
        }

        if (in_array($normalizedFrom, array('dev-master', 'dev-trunk', 'dev-default'), true)) {
            $normalizedFrom = VersionParser::DEFAULT_BRANCH_ALIAS;
        }
        if (in_array($normalizedTo, array('dev-master', 'dev-trunk', 'dev-default'), true)) {
            $normalizedTo = VersionParser::DEFAULT_BRANCH_ALIAS;
        }

        if (strpos($normalizedFrom, 'dev-') === 0 || strpos($normalizedTo, 'dev-') === 0) {
            return true;
        }

        $sorted = Semver::sort(array($normalizedTo, $normalizedFrom));

        return $sorted[0] === $normalizedFrom;
    }
}
