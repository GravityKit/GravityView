<?php

/*
 * WP_CLI: Copied and adapted from vendor/composer/composer/src/Composer/Json/JsonManipulator.php. Changes marked `WP_CLI`.
 *
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WP_CLI; // WP_CLI

use Composer\Json\JsonFile; // WP_CLI
use Composer\Repository\PlatformRepository;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class JsonManipulator
{
    private static $DEFINES = '(?(DEFINE)
       (?<number>   -? (?= [1-9]|0(?!\d) ) \d+ (\.\d+)? ([eE] [+-]? \d+)? )
       (?<boolean>   true | false | null )
       (?<string>    " ([^"\\\\]* | \\\\ ["\\\\bfnrt\/] | \\\\ u [0-9a-f]{4} )* " )
       (?<array>     \[  (?:  (?&json) \s* (?: , (?&json) \s* )*  )?  \s* \] )
       (?<pair>      \s* (?&string) \s* : (?&json) \s* )
       (?<object>    \{  (?:  (?&pair)  (?: , (?&pair)  )*  )?  \s* \} )
       (?<json>   \s* (?: (?&number) | (?&boolean) | (?&string) | (?&array) | (?&object) ) )
    )';

    private $contents;
    private $newline;
    private $indent;

    public function __construct($contents)
    {
        $contents = trim($contents);
        if ($contents === '') {
            $contents = '{}';
        }
        if (!$this->pregMatch('#^\{(.*)\}$#s', $contents)) {
            throw new \InvalidArgumentException('The json file must be an object ({})');
        }
        $this->newline = false !== strpos($contents, "\r\n") ? "\r\n" : "\n";
        $this->contents = $contents === '{}' ? '{' . $this->newline . '}' : $contents;
        $this->detectIndenting();
    }

    public function getContents()
    {
        return $this->contents . $this->newline;
    }

    public function addLink($type, $package, $constraint, $sortPackages = false, $caseInsensitive = false) // WP_CLI: caseInsensitive.
    {
        $decoded = JsonFile::parseJson($this->contents);

        // no link of that type yet
        if (!isset($decoded[$type])) {
            return $this->addMainKey($type, array($package => $constraint));
        }

        $regex = '{'.self::$DEFINES.'^(?P<start>\s*\{\s*(?:(?&string)\s*:\s*(?&json)\s*,\s*)*?)'.
            '(?P<property>'.preg_quote(JsonFile::encode($type)).'\s*:\s*)(?P<value>(?&json))(?P<end>.*)}sx';
        if (!$this->pregMatch($regex, $this->contents, $matches)) {
            return false;
        }

		// WP_CLI: begin caseInsensitive.
		if ($caseInsensitive) {
			// Just zap any existing packages first in a case insensitive manner.
			$this->removeSubNode($type, $package, $caseInsensitive);
			return $this->addLink($type, $package, $constraint, $sortPackages);
		}
		// WP_CLI: end caseInsensitive.

        $links = $matches['value'];

        // try to find existing link
        $packageRegex = str_replace('/', '\\\\?/', preg_quote($package));
        $regex = '{'.self::$DEFINES.'"(?P<package>'.$packageRegex.')"(\s*:\s*)(?&string)}ix';
        if ($this->pregMatch($regex, $links, $packageMatches)) {
            // update existing link
            $existingPackage = $packageMatches['package'];
            $packageRegex = str_replace('/', '\\\\?/', preg_quote($existingPackage));
            $links = preg_replace_callback('{'.self::$DEFINES.'"'.$packageRegex.'"(?P<separator>\s*:\s*)(?&string)}ix', function ($m) use ($existingPackage, $constraint) {
                return JsonFile::encode(str_replace('\\/', '/', $existingPackage)) . $m['separator'] . '"' . $constraint . '"';
            }, $links);
        } else {
            if ($this->pregMatch('#^\s*\{\s*\S+.*?(\s*\}\s*)$#s', $links, $match)) {
                // link missing but non empty links
                $links = preg_replace(
                    '{'.preg_quote($match[1]).'$}',
                    // addcslashes is used to double up backslashes/$ since preg_replace resolves them as back references otherwise, see #1588
                    addcslashes(',' . $this->newline . $this->indent . $this->indent . JsonFile::encode($package).': '.JsonFile::encode($constraint) . $match[1], '\\$'),
                    $links
                );
            } else {
                // links empty
                $links = '{' . $this->newline .
                    $this->indent . $this->indent . JsonFile::encode($package).': '.JsonFile::encode($constraint) . $this->newline .
                    $this->indent . '}';
            }
        }

        if (true === $sortPackages) {
            $requirements = json_decode($links, true);
            $this->sortPackages($requirements);
            $links = $this->format($requirements);
        }

        $this->contents = $matches['start'] . $matches['property'] . $links . $matches['end'];

        return true;
    }

    /**
     * Sorts packages by importance (platform packages first, then PHP dependencies) and alphabetically.
     *
     * @link https://getcomposer.org/doc/02-libraries.md#platform-packages
     *
     * @param array $packages
     */
    private function sortPackages(array &$packages = array())
    {
        $prefix = function ($requirement) {
            if (preg_match(PlatformRepository::PLATFORM_PACKAGE_REGEX, $requirement)) {
                return preg_replace(
                    array(
                        '/^php/',
                        '/^hhvm/',
                        '/^ext/',
                        '/^lib/',
                        '/^\D/',
                    ),
                    array(
                        '0-$0',
                        '1-$0',
                        '2-$0',
                        '3-$0',
                        '4-$0',
                    ),
                    $requirement
                );
            }

            return '5-'.$requirement;
        };

        uksort($packages, function ($a, $b) use ($prefix) {
            return strnatcmp($prefix($a), $prefix($b));
        });
    }

    public function addRepository($name, $config)
    {
        return $this->addSubNode('repositories', $name, $config);
    }

    public function removeRepository($name)
    {
        return $this->removeSubNode('repositories', $name);
    }

    public function addConfigSetting($name, $value)
    {
        return $this->addSubNode('config', $name, $value);
    }

    public function removeConfigSetting($name)
    {
        return $this->removeSubNode('config', $name);
    }

    public function addProperty($name, $value)
    {
        if (substr($name, 0, 6) === 'extra.') {
            return $this->addSubNode('extra', substr($name, 6), $value);
        }

        return $this->addMainKey($name, $value);
    }

    public function removeProperty($name)
    {
        if (substr($name, 0, 6) === 'extra.') {
            return $this->removeSubNode('extra', substr($name, 6));
        }

        return $this->removeMainKey($name);
    }

    public function addSubNode($mainNode, $name, $value, $caseInsensitive = false) // WP_CLI: caseInsensitive.
    {
        $decoded = JsonFile::parseJson($this->contents);

        $subName = null;
        if (in_array($mainNode, array('config', 'extra')) && false !== strpos($name, '.')) {
            list($name, $subName) = explode('.', $name, 2);
        }

        // no main node yet
        if (!isset($decoded[$mainNode])) {
            if ($subName !== null) {
                $this->addMainKey($mainNode, array($name => array($subName => $value)));
            } else {
                $this->addMainKey($mainNode, array($name => $value));
            }

            return true;
        }

        // main node content not match-able
        $nodeRegex = '{'.self::$DEFINES.'^(?P<start> \s* \{ \s* (?: (?&string) \s* : (?&json) \s* , \s* )*?'.
            preg_quote(JsonFile::encode($mainNode)).'\s*:\s*)(?P<content>(?&object))(?P<end>.*)}sx';

        try {
            if (!$this->pregMatch($nodeRegex, $this->contents, $match)) {
                return false;
            }
        } catch (\RuntimeException $e) {
            if ($e->getCode() === PREG_BACKTRACK_LIMIT_ERROR) {
                return false;
            }
            throw $e;
        }

		// WP_CLI: begin caseInsensitive.
		if ($caseInsensitive) {
			// Just zap any existing names first in a case insensitive manner.
			$this->removeSubNode($mainNode, $name, $caseInsensitive);
			return $this->addSubNode($mainNode, $name, $value);
		}
		// WP_CLI: end caseInsensitive.

        $children = $match['content'];
        // invalid match due to un-regexable content, abort
        if (!@json_decode($children)) {
            return false;
        }

        $that = $this;

        // child exists
        $childRegex = '{'.self::$DEFINES.'(?P<start>"'.preg_quote($name).'"\s*:\s*)(?P<content>(?&json))(?P<end>,?)}x';
        if ($this->pregMatch($childRegex, $children, $matches)) {
            $children = preg_replace_callback($childRegex, function ($matches) use ($name, $subName, $value, $that) {
                if ($subName !== null) {
                    $curVal = json_decode($matches['content'], true);
                    if (!is_array($curVal)) {
                        $curVal = array();
                    }
                    $curVal[$subName] = $value;
                    $value = $curVal;
                }

                return $matches['start'] . $that->format($value, 1) . $matches['end'];
            }, $children);
        } else {
            $this->pregMatch('#^{ \s*? (?P<content>\S+.*?)? (?P<trailingspace>\s*) }$#sx', $children, $match);

            $whitespace = '';
            if (!empty($match['trailingspace'])) {
                $whitespace = $match['trailingspace'];
            }

            if (!empty($match['content'])) {
                if ($subName !== null) {
                    $value = array($subName => $value);
                }

                // child missing but non empty children
                $children = preg_replace(
                    '#'.$whitespace.'}$#',
                    addcslashes(',' . $this->newline . $this->indent . $this->indent . JsonFile::encode($name).': '.$this->format($value, 1) . $whitespace . '}', '\\$'),
                    $children
                );
            } else {
                if ($subName !== null) {
                    $value = array($subName => $value);
                }

                // children present but empty
                $children = '{' . $this->newline . $this->indent . $this->indent . JsonFile::encode($name).': '.$this->format($value, 1) . $whitespace . '}';
            }
        }

        $this->contents = preg_replace_callback($nodeRegex, function ($m) use ($children) {
            return $m['start'] . $children . $m['end'];
        }, $this->contents);

        return true;
    }

    public function removeSubNode($mainNode, $name, $caseInsensitive = false) // WP_CLI: caseInsensitive.
    {
        $decoded = JsonFile::parseJson($this->contents);

        // no node or empty node
        if (empty($decoded[$mainNode])) {
            return true;
        }

		// WP_CLI: begin caseInsensitive.
		if ( $caseInsensitive ) {
			// This is more or less a copy of the code at the start of `addLink()` above.
			$regex = '{'.self::$DEFINES.'^(?P<start>\s*\{\s*(?:(?&string)\s*:\s*(?&json)\s*,\s*)*?)'.
				'(?P<property>'.preg_quote(JsonFile::encode($mainNode)).'\s*:\s*)(?P<value>(?&json))(?P<end>.*)}sx';
			if (!$this->pregMatch($regex, $this->contents, $matches)) {
				return true;
			}

			$value = $matches['value']; // Renamed from `$links` in `addLink()` case above.

			// try to find existing values
			$nameRegex = str_replace('/', '\\\\?/', preg_quote($name)); // Renamed from `$packageRegex` in `addLink()` case above.
			$regex = '{'.self::$DEFINES.'"(?P<name>'.$nameRegex.')"(\s*:\s*)(?&json)}ix'; // Need `(?&json)` PCRE subroutine here, as opposed to `(?&string)` in `addLink()` case.
			if (preg_match_all($regex, $value, $nameMatches)) {
				// Just zap them all individually in a case sensitive manner.
				foreach ( $nameMatches['name'] as $nameMatch ) {
					$this->removeSubNode($mainNode, $nameMatch);
				}
			}

			return true;
		}
		// WP_CLI: end caseInsensitive.

        // no node content match-able
        $nodeRegex = '{'.self::$DEFINES.'^(?P<start> \s* \{ \s* (?: (?&string) \s* : (?&json) \s* , \s* )*?'.
            preg_quote(JsonFile::encode($mainNode)).'\s*:\s*)(?P<content>(?&object))(?P<end>.*)}sx';
        try {
            if (!$this->pregMatch($nodeRegex, $this->contents, $match)) {
                return false;
            }
        } catch (\RuntimeException $e) {
            if ($e->getCode() === PREG_BACKTRACK_LIMIT_ERROR) {
                return false;
            }
            throw $e;
        }

        $children = $match['content'];

        // invalid match due to un-regexable content, abort
        if (!@json_decode($children, true)) {
            return false;
        }

        $subName = null;
        if (in_array($mainNode, array('config', 'extra')) && false !== strpos($name, '.')) {
            list($name, $subName) = explode('.', $name, 2);
        }

        // no node to remove
        if (!isset($decoded[$mainNode][$name]) || ($subName && !isset($decoded[$mainNode][$name][$subName]))) {
            return true;
        }

        // try and find a match for the subkey
        if ($this->pregMatch('{"'.preg_quote($name).'"\s*:}i', $children)) {
            // find best match for the value of "name"
            if (preg_match_all('{'.self::$DEFINES.'"'.preg_quote($name).'"\s*:\s*(?:(?&json))}x', $children, $matches)) {
                $bestMatch = '';
                foreach ($matches[0] as $match) {
                    if (strlen($bestMatch) < strlen($match)) {
                        $bestMatch = $match;
                    }
                }
                $childrenClean = preg_replace('{,\s*'.preg_quote($bestMatch).'}', '', $children, -1, $count); // WP_CLI: As the preg_match_all() above is case-sensitive, so should this be.
                if (1 !== $count) {
                    $childrenClean = preg_replace('{'.preg_quote($bestMatch).'\s*,?\s*}', '', $childrenClean, -1, $count); // WP_CLI: ditto.
                    if (1 !== $count) {
                        return false;
                    }
                }
            }
        } else {
            $childrenClean = $children;
        }

        // no child data left, $name was the only key in
        $this->pregMatch('#^{ \s*? (?P<content>\S+.*?)? (?P<trailingspace>\s*) }$#sx', $childrenClean, $match);
        if (empty($match['content'])) {
            $newline = $this->newline;
            $indent = $this->indent;

            $this->contents = preg_replace_callback($nodeRegex, function ($matches) use ($indent, $newline) {
                return $matches['start'] . '{' . $newline . $indent . '}' . $matches['end'];
            }, $this->contents);

            // we have a subname, so we restore the rest of $name
            if ($subName !== null) {
                $curVal = json_decode($children, true);
                unset($curVal[$name][$subName]);
                $this->addSubNode($mainNode, $name, $curVal[$name]);
            }

            return true;
        }

        $that = $this;
        $this->contents = preg_replace_callback($nodeRegex, function ($matches) use ($that, $name, $subName, $childrenClean) {
            if ($subName !== null) {
                $curVal = json_decode($matches['content'], true);
                unset($curVal[$name][$subName]);
                $childrenClean = $that->format($curVal, 0);
            }

            return $matches['start'] . $childrenClean . $matches['end'];
        }, $this->contents);

        return true;
    }

    public function addMainKey($key, $content)
    {
        $decoded = JsonFile::parseJson($this->contents);
        $content = $this->format($content);

        // key exists already
        $regex = '{'.self::$DEFINES.'^(?P<start>\s*\{\s*(?:(?&string)\s*:\s*(?&json)\s*,\s*)*?)'.
            '(?P<key>'.preg_quote(JsonFile::encode($key)).'\s*:\s*(?&json))(?P<end>.*)}sx';
        if (isset($decoded[$key]) && $this->pregMatch($regex, $this->contents, $matches)) {
            // invalid match due to un-regexable content, abort
            if (!@json_decode('{'.$matches['key'].'}')) {
                return false;
            }

            $this->contents = $matches['start'] . JsonFile::encode($key).': '.$content . $matches['end'];

            return true;
        }

        // append at the end of the file and keep whitespace
        if ($this->pregMatch('#[^{\s](\s*)\}$#', $this->contents, $match)) {
            $this->contents = preg_replace(
                '#'.$match[1].'\}$#',
                addcslashes(',' . $this->newline . $this->indent . JsonFile::encode($key). ': '. $content . $this->newline . '}', '\\$'),
                $this->contents
            );

            return true;
        }

        // append at the end of the file
        $this->contents = preg_replace(
            '#\}$#',
            addcslashes($this->indent . JsonFile::encode($key). ': '.$content . $this->newline . '}', '\\$'),
            $this->contents
        );

        return true;
    }

    public function removeMainKey($key)
    {
        $decoded = JsonFile::parseJson($this->contents);

        if (!isset($decoded[$key])) {
            return true;
        }

        // key exists already
        $regex = '{'.self::$DEFINES.'^(?P<start>\s*\{\s*(?:(?&string)\s*:\s*(?&json)\s*,\s*)*?)'.
            '(?P<removal>'.preg_quote(JsonFile::encode($key)).'\s*:\s*(?&json))\s*,?\s*(?P<end>.*)}sx';
        if ($this->pregMatch($regex, $this->contents, $matches)) {
            // invalid match due to un-regexable content, abort
            if (!@json_decode('{'.$matches['removal'].'}')) {
                return false;
            }

            // check that we are not leaving a dangling comma on the previous line if the last line was removed
            if (preg_match('#,\s*$#', $matches['start']) && preg_match('#^\}$#', $matches['end'])) {
                $matches['start'] = rtrim(preg_replace('#,(\s*)$#', '$1', $matches['start']), $this->indent);
            }

            $this->contents = $matches['start'] . $matches['end'];
            if (preg_match('#^\{\s*\}\s*$#', $this->contents)) {
                $this->contents = "{\n}";
            }

            return true;
        }

        return false;
    }

    public function format($data, $depth = 0)
    {
        if (is_array($data)) {
            reset($data);

            if (is_numeric(key($data))) {
                foreach ($data as $key => $val) {
                    $data[$key] = $this->format($val, $depth + 1);
                }

                return '['.implode(', ', $data).']';
            }

            $out = '{' . $this->newline;
            $elems = array();
            foreach ($data as $key => $val) {
                $elems[] = str_repeat($this->indent, $depth + 2) . JsonFile::encode($key). ': '.$this->format($val, $depth + 1);
            }

            return $out . implode(','.$this->newline, $elems) . $this->newline . str_repeat($this->indent, $depth + 1) . '}';
        }

        return JsonFile::encode($data);
    }

    protected function detectIndenting()
    {
        if ($this->pregMatch('{^([ \t]+)"}m', $this->contents, $match)) {
            $this->indent = $match[1];
        } else {
            $this->indent = '    ';
        }
    }

    protected function pregMatch($re, $str, &$matches = array())
    {
        $count = preg_match($re, $str, $matches);

        if ($count === false) {
            switch (preg_last_error()) {
                case PREG_NO_ERROR:
                    throw new \RuntimeException('Failed to execute regex: PREG_NO_ERROR', PREG_NO_ERROR);
                case PREG_INTERNAL_ERROR:
                    throw new \RuntimeException('Failed to execute regex: PREG_INTERNAL_ERROR', PREG_INTERNAL_ERROR);
                case PREG_BACKTRACK_LIMIT_ERROR:
                    throw new \RuntimeException('Failed to execute regex: PREG_BACKTRACK_LIMIT_ERROR', PREG_BACKTRACK_LIMIT_ERROR);
                case PREG_RECURSION_LIMIT_ERROR:
                    throw new \RuntimeException('Failed to execute regex: PREG_RECURSION_LIMIT_ERROR', PREG_RECURSION_LIMIT_ERROR);
                case PREG_BAD_UTF8_ERROR:
                    throw new \RuntimeException('Failed to execute regex: PREG_BAD_UTF8_ERROR', PREG_BAD_UTF8_ERROR);
                case PREG_BAD_UTF8_OFFSET_ERROR:
                    throw new \RuntimeException('Failed to execute regex: PREG_BAD_UTF8_OFFSET_ERROR', PREG_BAD_UTF8_OFFSET_ERROR);
                case 6: // PREG_JIT_STACKLIMIT_ERROR
                    if (PHP_VERSION_ID > 70000) {
                        throw new \RuntimeException('Failed to execute regex: PREG_JIT_STACKLIMIT_ERROR', 6);
                    }
                    // fallthrough

                default:
                    throw new \RuntimeException('Failed to execute regex: Unknown error');
            }
        }

        return $count;
    }
}
