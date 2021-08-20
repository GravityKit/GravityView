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

use stdClass;

/**
 * Tests URLs against NO_PROXY patterns
 */
class NoProxyPattern
{
    /**
     * @var string[]
     */
    protected $hostNames = array();

    /**
     * @var (null|object)[]
     */
    protected $rules = array();

    /**
     * @var bool
     */
    protected $noproxy;

    /**
     * @param string $pattern NO_PROXY pattern
     */
    public function __construct($pattern)
    {
        $this->hostNames = preg_split('{[\s,]+}', $pattern, -1, PREG_SPLIT_NO_EMPTY);
        $this->noproxy = empty($this->hostNames) || '*' === $this->hostNames[0];
    }

    /**
     * Returns true if a URL matches the NO_PROXY pattern
     *
     * @param string $url
     *
     * @return bool
     */
    public function test($url)
    {
        if ($this->noproxy) {
            return true;
        }

        if (!$urlData = $this->getUrlData($url)) {
            return false;
        }

        foreach ($this->hostNames as $index => $hostName) {
            if ($this->match($index, $hostName, $urlData)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns false is the url cannot be parsed, otherwise a data object
     *
     * @param string $url
     *
     * @return bool|stdClass
     */
    protected function getUrlData($url)
    {
        if (!$host = parse_url($url, PHP_URL_HOST)) {
            return false;
        }

        $port = parse_url($url, PHP_URL_PORT);

        if (empty($port)) {
            switch (parse_url($url, PHP_URL_SCHEME)) {
                case 'http':
                    $port = 80;
                    break;
                case 'https':
                    $port = 443;
                    break;
            }
        }

        $hostName = $host . ($port ? ':' . $port : '');
        list($host, $port, $err) = $this->splitHostPort($hostName);

        if ($err || !$this->ipCheckData($host, $ipdata)) {
            return false;
        }

        return $this->makeData($host, $port, $ipdata);
    }

    /**
     * Returns true if the url is matched by a rule
     *
     * @param int      $index
     * @param string   $hostName
     * @param stdClass $url
     *
     * @return bool
     */
    protected function match($index, $hostName, $url)
    {
        if (!$rule = $this->getRule($index, $hostName)) {
            // Data must have been misformatted
            return false;
        }

        if ($rule->ipdata) {
            // Match ipdata first
            if (!$url->ipdata) {
                return false;
            }

            if ($rule->ipdata->netmask) {
                return $this->matchRange($rule->ipdata, $url->ipdata);
            }

            $match = $rule->ipdata->ip === $url->ipdata->ip;
        } else {
            // Match host and port
            $haystack = substr($url->name, -strlen($rule->name));
            $match = stripos($haystack, $rule->name) === 0;
        }

        if ($match && $rule->port) {
            $match = $rule->port === $url->port;
        }

        return $match;
    }

    /**
     * Returns true if the target ip is in the network range
     *
     * @param stdClass $network
     * @param stdClass $target
     *
     * @return bool
     */
    protected function matchRange(stdClass $network, stdClass $target)
    {
        $net = unpack('C*', $network->ip);
        $mask = unpack('C*', $network->netmask);
        $ip = unpack('C*', $target->ip);

        for ($i = 1; $i < 17; ++$i) {
            if (($net[$i] & $mask[$i]) !== ($ip[$i] & $mask[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Finds or creates rule data for a hostname
     *
     * @param int    $index
     * @param string $hostName
     *
     * @return null|stdClass Null if the hostname is invalid
     */
    private function getRule($index, $hostName)
    {
        if (array_key_exists($index, $this->rules)) {
            return $this->rules[$index];
        }

        $this->rules[$index] = null;
        list($host, $port, $err) = $this->splitHostPort($hostName);

        if ($err || !$this->ipCheckData($host, $ipdata, true)) {
            return null;
        }

        $this->rules[$index] = $this->makeData($host, $port, $ipdata);

        return $this->rules[$index];
    }

    /**
     * Creates an object containing IP data if the host is an IP address
     *
     * @param string        $host
     * @param null|stdClass $ipdata      Set by method if IP address found
     * @param bool          $allowPrefix Whether a CIDR prefix-length is expected
     *
     * @return bool False if the host contains invalid data
     */
    private function ipCheckData($host, &$ipdata, $allowPrefix = false)
    {
        $ipdata = null;
        $netmask = null;
        $prefix = null;
        $modified = false;

        // Check for a CIDR prefix-length
        if (strpos($host, '/') !== false) {
            list($host, $prefix) = explode('/', $host);

            if (!$allowPrefix || !$this->validateInt($prefix, 0, 128)) {
                return false;
            }
            $prefix = (int) $prefix;
            $modified = true;
        }

        // See if this is an ip address
        if (!filter_var($host, FILTER_VALIDATE_IP)) {
            return !$modified;
        }

        list($ip, $size) = $this->ipGetAddr($host);

        if ($prefix !== null) {
            // Check for a valid prefix
            if ($prefix > $size * 8) {
                return false;
            }

            list($ip, $netmask) = $this->ipGetNetwork($ip, $size, $prefix);
        }

        $ipdata = $this->makeIpData($ip, $size, $netmask);

        return true;
    }

    /**
     * Returns an array of the IP in_addr and its byte size
     *
     * IPv4 addresses are always mapped to IPv6, which simplifies handling
     * and comparison.
     *
     * @param string $host
     *
     * @return mixed[] in_addr, size
     */
    private function ipGetAddr($host)
    {
        $ip = inet_pton($host);
        $size = strlen($ip);
        $mapped = $this->ipMapTo6($ip, $size);

        return array($mapped, $size);
    }

    /**
     * Returns the binary network mask mapped to IPv6
     *
     * @param string $prefix CIDR prefix-length
     * @param int    $size   Byte size of in_addr
     *
     * @return string
     */
    private function ipGetMask($prefix, $size)
    {
        $mask = '';

        if ($ones = floor($prefix / 8)) {
            $mask = str_repeat(chr(255), $ones);
        }

        if ($remainder = $prefix % 8) {
            $mask .= chr(0xff ^ (0xff >> $remainder));
        }

        $mask = str_pad($mask, $size, chr(0));

        return $this->ipMapTo6($mask, $size);
    }

    /**
     * Calculates and returns the network and mask
     *
     * @param string $rangeIp IP in_addr
     * @param int    $size    Byte size of in_addr
     * @param string $prefix  CIDR prefix-length
     *
     * @return string[] network in_addr, binary mask
     */
    private function ipGetNetwork($rangeIp, $size, $prefix)
    {
        $netmask = $this->ipGetMask($prefix, $size);

        // Get the network from the address and mask
        $mask = unpack('C*', $netmask);
        $ip = unpack('C*', $rangeIp);
        $net = '';

        for ($i = 1; $i < 17; ++$i) {
            $net .= chr($ip[$i] & $mask[$i]);
        }

        return array($net, $netmask);
    }

    /**
     * Maps an IPv4 address to IPv6
     *
     * @param string $binary in_addr
     * @param int    $size   Byte size of in_addr
     *
     * @return string Mapped or existing in_addr
     */
    private function ipMapTo6($binary, $size)
    {
        if ($size === 4) {
            $prefix = str_repeat(chr(0), 10) . str_repeat(chr(255), 2);
            $binary = $prefix . $binary;
        }

        return $binary;
    }

    /**
     * Creates a rule data object
     *
     * @param string        $host
     * @param int           $port
     * @param null|stdClass $ipdata
     *
     * @return stdClass
     */
    private function makeData($host, $port, $ipdata)
    {
        return (object) array(
            'host' => $host,
            'name' => '.' . ltrim($host, '.'),
            'port' => $port,
            'ipdata' => $ipdata,
        );
    }

    /**
     * Creates an ip data object
     *
     * @param string      $ip      in_addr
     * @param int         $size    Byte size of in_addr
     * @param null|string $netmask Network mask
     *
     * @return stdClass
     */
    private function makeIpData($ip, $size, $netmask)
    {
        return (object) array(
            'ip' => $ip,
            'size' => $size,
            'netmask' => $netmask,
        );
    }

    /**
     * Splits the hostname into host and port components
     *
     * @param string $hostName
     *
     * @return mixed[] host, port, if there was error
     */
    private function splitHostPort($hostName)
    {
        // host, port, err
        $error = array('', '', true);
        $port = 0;
        $ip6 = '';

        // Check for square-bracket notation
        if ($hostName[0] === '[') {
            $index = strpos($hostName, ']');

            // The smallest ip6 address is ::
            if (false === $index || $index < 3) {
                return $error;
            }

            $ip6 = substr($hostName, 1, $index - 1);
            $hostName = substr($hostName, $index + 1);

            if (strpbrk($hostName, '[]') !== false || substr_count($hostName, ':') > 1) {
                return $error;
            }
        }

        if (substr_count($hostName, ':') === 1) {
            $index = strpos($hostName, ':');
            $port = substr($hostName, $index + 1);
            $hostName = substr($hostName, 0, $index);

            if (!$this->validateInt($port, 1, 65535)) {
                return $error;
            }

            $port = (int) $port;
        }

        $host = $ip6 . $hostName;

        return array($host, $port, false);
    }

    /**
     * Wrapper around filter_var FILTER_VALIDATE_INT
     *
     * @param string $int
     * @param int    $min
     * @param int    $max
     */
    private function validateInt($int, $min, $max)
    {
        $options = array(
            'options' => array(
                'min_range' => $min,
                'max_range' => $max,
            ),
        );

        return false !== filter_var($int, FILTER_VALIDATE_INT, $options);
    }
}
