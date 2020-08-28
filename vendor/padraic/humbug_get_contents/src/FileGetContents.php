<?php

/*
 * This file is part of the Humbug package.
 *
 * (c) 2015 Pádraic Brady <padraic.brady@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Humbug;

use Composer\CaBundle\CaBundle;
use RuntimeException;

/**
 * This is largely extracted from the Composer Installer where originally implemented.
 */
class FileGetContents
{
    /**
     * @var array|null
     *
     * @private
     */
    protected static $lastResponseHeaders;

    /**
     * @var array|null
     *
     * @private
     */
    protected static $nextRequestHeaders;

    protected $options = array('http' => array());

    public function __construct()
    {
        $options = $this->getTlsStreamContextDefaults(null);
        $this->options = array_replace_recursive($this->options, $options);
    }

    /**
     * @param string              $filename Name of the file to read.
     * @param resource|array|null $context A valid context resource created with `stream_context_create()`. If you don't
     *                                     need to use a custom context, you can skip this parameter.
     *
     * @return bool|string The read data or `false` on failure.
     */
    public function get($filename, $context = null)
    {
        $context = $this->getStreamContext($filename);
        $context = self::setHttpHeaders($context);

        $result = file_get_contents($filename, null, $context);

        self::setLastResponseHeaders($http_response_header);

        return $result;
    }

    /**
     * @param array $headers HTTP response headers.
     *
     * @final Since 1.1.0
     */
    public static function setLastResponseHeaders($headers)
    {
        self::$lastResponseHeaders = $headers;
    }

    /**
     * @return array|null HTTP response headers for the last response recorded or `null` if none has been recorded.
     *
     * @final Since 1.1.0
     */
    public static function getLastResponseHeaders()
    {
        return self::$lastResponseHeaders;
    }

    /**
     * @param array $headers An indexed or associative array.
     *
     * @final since 1.1.0
     */
    public static function setNextRequestHeaders(array $headers)
    {
        self::$nextRequestHeaders = $headers;
    }

    /**
     * @return bool
     *
     * @final since 1.1.0
     */
    public static function hasNextRequestHeaders()
    {
        return null !== self::$nextRequestHeaders;
    }

    /**
     * @return array|null An indexed or associative array whence there is any headers, `null` otherwise.
     */
    public static function getNextRequestHeaders()
    {
        $return = self::$nextRequestHeaders;
        self::$nextRequestHeaders = null;

        return $return;
    }

    /**
     * @param resource|array|null $context A valid context resource created with `stream_context_create()`. If you don't
     *                                     need to use a custom context, you can skip this parameter.
     *
     * @return resource|array|null Context to which the headers has been set.
     *
     * @private since 1.1.0
     *
     * TODO (2.0.0): change the name to reflect on the immutable side.
     */
    public static function setHttpHeaders($context)
    {
        $headers = self::getNextRequestHeaders();

        if (empty($headers)) {
            return $context;
        }

        $options = stream_context_get_options($context);
        if (!isset($options['http'])) {
            $options['http'] = array('header' => array());
        } elseif (!isset($options['http']['header'])) {
            $options['http']['header'] = array();
        } elseif (is_string($options['http']['header'])) {
            $options['http']['header'] = explode("\r\n", $options['http']['header']);
        }

        $headers = empty($options['http']['headers']) ? $headers : array_merge($options['http']['headers'], $headers);

        stream_context_set_option(
            $context,
            'http',
            'header',
            $headers
        );

        return $context;
    }

    /**
     * @param string $url URL path to access to the file to read.
     *
     * @return resource
     *
     * @final since 1.1.0
     */
    protected function getStreamContext($url)
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (PHP_VERSION_ID < 50600) {
            $this->options['ssl']['CN_match'] = $host;
            $this->options['ssl']['SNI_server_name'] = $host;
        }

        return $this->getMergedStreamContext($url);
    }

    /**
     * @param string $cafile
     *
     * @return array
     *
     * @private since 1.1.0
     *
     * TODO (2.0.0): remove argument (unnused in the codebase) and rename this method as an `init*` as is used in the
     *               constructor anymore
     */
    protected function getTlsStreamContextDefaults($cafile)
    {
        $ciphers = implode(':', array(
            'ECDHE-RSA-AES128-GCM-SHA256',
            'ECDHE-ECDSA-AES128-GCM-SHA256',
            'ECDHE-RSA-AES256-GCM-SHA384',
            'ECDHE-ECDSA-AES256-GCM-SHA384',
            'DHE-RSA-AES128-GCM-SHA256',
            'DHE-DSS-AES128-GCM-SHA256',
            'kEDH+AESGCM',
            'ECDHE-RSA-AES128-SHA256',
            'ECDHE-ECDSA-AES128-SHA256',
            'ECDHE-RSA-AES128-SHA',
            'ECDHE-ECDSA-AES128-SHA',
            'ECDHE-RSA-AES256-SHA384',
            'ECDHE-ECDSA-AES256-SHA384',
            'ECDHE-RSA-AES256-SHA',
            'ECDHE-ECDSA-AES256-SHA',
            'DHE-RSA-AES128-SHA256',
            'DHE-RSA-AES128-SHA',
            'DHE-DSS-AES128-SHA256',
            'DHE-RSA-AES256-SHA256',
            'DHE-DSS-AES256-SHA',
            'DHE-RSA-AES256-SHA',
            'AES128-GCM-SHA256',
            'AES256-GCM-SHA384',
            'AES128-SHA256',
            'AES256-SHA256',
            'AES128-SHA',
            'AES256-SHA',
            'AES',
            'CAMELLIA',
            'DES-CBC3-SHA',
            '!aNULL',
            '!eNULL',
            '!EXPORT',
            '!DES',
            '!RC4',
            '!MD5',
            '!PSK',
            '!aECDH',
            '!EDH-DSS-DES-CBC3-SHA',
            '!EDH-RSA-DES-CBC3-SHA',
            '!KRB5-DES-CBC3-SHA',
            '!ADH'
        ));

        $options = array(
            'ssl' => array(
                'ciphers' => $ciphers,
                'verify_peer' => true,
                'verify_depth' => 7,
                'SNI_enabled' => true,
            )
        );

        if (!$cafile) {
            $cafile = CaBundle::getSystemCaRootBundlePath();
        }
        if (is_dir($cafile)) {
            $options['ssl']['capath'] = $cafile;
        } elseif ($cafile) {
            $options['ssl']['cafile'] = $cafile;
        } else {
            throw new RuntimeException('A valid cafile could not be located locally.');
        }

        if (version_compare(PHP_VERSION, '5.4.13') >= 0) {
            $options['ssl']['disable_compression'] = true;
        }

        return $options;
    }

    /**
     * Function copied from Composer\Util\StreamContextFactory::getContext
     *
     * This function is part of Composer.
     *
     * (c) Nils Adermann <naderman@naderman.de>
     *     Jordi Boggiano <j.boggiano@seld.be>
     *
     * @param string $url URL the context is to be used for
     *
     * @throws \RuntimeException If https proxy required and OpenSSL uninstalled
     *
     * @return resource Default context
     *
     * @final since 1.1.0
     */
    protected function getMergedStreamContext($url)
    {
        $options = $this->options;

        // See CVE-2016-5385, due to (emulation of) header copying with PHP web SAPIs into HTTP_* variables,
        // HTTP_PROXY can be set by an user to any value he wants by setting the Proxy header.
        // Mitigate the vulnerability by only allowing CLI SAPIs to use HTTP(S)_PROXY environment variables.
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            // Handle system proxy
            if (!empty($_SERVER['HTTP_PROXY']) || !empty($_SERVER['http_proxy'])) {
                // Some systems seem to rely on a lowercased version instead...
                $proxy = parse_url(!empty($_SERVER['http_proxy']) ? $_SERVER['http_proxy'] : $_SERVER['HTTP_PROXY']);
            }
        }

        if (!empty($proxy)) {
            $proxyURL = isset($proxy['scheme']) ? $proxy['scheme'].'://' : '';
            $proxyURL .= isset($proxy['host']) ? $proxy['host'] : '';

            if (isset($proxy['port'])) {
                $proxyURL .= ':'.$proxy['port'];
            } elseif ('http://' == substr($proxyURL, 0, 7)) {
                $proxyURL .= ':80';
            } elseif ('https://' == substr($proxyURL, 0, 8)) {
                $proxyURL .= ':443';
            }

            // http(s):// is not supported in proxy
            $proxyURL = str_replace(array('http://', 'https://'), array('tcp://', 'ssl://'), $proxyURL);

            if (0 === strpos($proxyURL, 'ssl:') && !extension_loaded('openssl')) {
                throw new RuntimeException('You must enable the openssl extension to use a proxy over https');
            }

            $options['http'] = array(
                'proxy' => $proxyURL,
            );

            // enabled request_fulluri unless it is explicitly disabled
            switch (parse_url($url, PHP_URL_SCHEME)) {
                case 'http': // default request_fulluri to true
                    $reqFullUriEnv = getenv('HTTP_PROXY_REQUEST_FULLURI');
                    if ($reqFullUriEnv === false || $reqFullUriEnv === '' || (strtolower($reqFullUriEnv) !== 'false' && (bool) $reqFullUriEnv)) {
                        $options['http']['request_fulluri'] = true;
                    }
                    break;
                case 'https': // default request_fulluri to true
                    $reqFullUriEnv = getenv('HTTPS_PROXY_REQUEST_FULLURI');
                    if ($reqFullUriEnv === false || $reqFullUriEnv === '' || (strtolower($reqFullUriEnv) !== 'false' && (bool) $reqFullUriEnv)) {
                        $options['http']['request_fulluri'] = true;
                    }
                    break;
            }


            if (isset($proxy['user'])) {
                $auth = urldecode($proxy['user']);
                if (isset($proxy['pass'])) {
                    $auth .= ':'.urldecode($proxy['pass']);
                }
                $auth = base64_encode($auth);

                $options['http']['header'] = "Proxy-Authorization: Basic {$auth}\r\n";
            }
        }

        return stream_context_create($options);
    }

    /**
     * @deprecated since 1.1.0 and will be removed in 2.0.0.
     */
    public static function getSystemCaRootBundlePath()
    {
        @trigger_error(
            'Deprecated since 1.1.0. Use `Composer\CaBundle\CaBundle::getSystemCaRootBundlePath()` instead.',
            E_USER_DEPRECATED
        );

        return CaBundle::getSystemCaRootBundlePath();
    }

    /**
     * @deprecated since 1.1.0 and will be removed in 2.0.0.
     */
    protected static function validateCaFile($contents)
    {
        // Assumes the CA is valid if PHP is vulnerable to
        // https://www.sektioneins.de/advisories/advisory-012013-php-openssl_x509_parse-memory-corruption-vulnerability.html
        if (!CaBundle::isOpensslParseSafe()) {
            return !empty($contents);
        }

        return (bool) openssl_x509_parse($contents);
    }
}
