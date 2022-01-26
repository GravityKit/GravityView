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

namespace Composer\Util\Http;

use Composer\Json\JsonFile;
use Composer\Pcre\Preg;
use Composer\Util\HttpDownloader;

/**
 * @phpstan-import-type Request from HttpDownloader
 */
class Response
{
    /** @var Request */
    private $request;
    /** @var int */
    private $code;
    /** @var string[] */
    private $headers;
    /** @var ?string */
    private $body;

    /**
     * @param Request  $request
     * @param int      $code
     * @param string[] $headers
     * @param ?string  $body
     */
    public function __construct(array $request, $code, array $headers, $body)
    {
        if (!isset($request['url'])) { // @phpstan-ignore-line
            throw new \LogicException('url key missing from request array');
        }
        $this->request = $request;
        $this->code = (int) $code;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getStatusMessage()
    {
        $value = null;
        foreach ($this->headers as $header) {
            if (Preg::isMatch('{^HTTP/\S+ \d+}i', $header)) {
                // In case of redirects, headers contain the headers of all responses
                // so we can not return directly and need to keep iterating
                $value = $header;
            }
        }

        return $value;
    }

    /**
     * @return string[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param  string  $name
     * @return ?string
     */
    public function getHeader($name)
    {
        return self::findHeaderValue($this->headers, $name);
    }

    /**
     * @return ?string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return mixed
     */
    public function decodeJson()
    {
        return JsonFile::parseJson($this->body, $this->request['url']);
    }

    /**
     * @return void
     * @phpstan-impure
     */
    public function collect()
    {
        /** @phpstan-ignore-next-line */
        $this->request = $this->code = $this->headers = $this->body = null;
    }

    /**
     * @param  string[]    $headers array of returned headers like from getLastHeaders()
     * @param  string      $name    header name (case insensitive)
     * @return string|null
     */
    public static function findHeaderValue(array $headers, $name)
    {
        $value = null;
        foreach ($headers as $header) {
            if (Preg::isMatch('{^'.preg_quote($name).':\s*(.+?)\s*$}i', $header, $match)) {
                $value = $match[1];
            }
        }

        return $value;
    }
}
