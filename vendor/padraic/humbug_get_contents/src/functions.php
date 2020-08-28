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

/**
 * Reads entire file into a string.
 *
 * @param string              $filename Name of the file to read.
 * @param bool                $use_include_path
 * @param resource|array|null $context A valid context resource created with `stream_context_create()`. If you don't
 *                                     need to use a custom context, you can skip this parameter.
 *
 * @return string|bool The read data or `false` on failure.
 */
function get_contents($filename, $use_include_path = false, $context = null)
{
    static $fileGetContents = null;

    if ('https' == parse_url($filename, PHP_URL_SCHEME) && PHP_VERSION_ID < 50600) {
        if (!isset($fileGetContents)) {
            $fileGetContents = new FileGetContents();
        }

        return $fileGetContents->get($filename, $context);
    } elseif (FileGetContents::hasNextRequestHeaders()) {
        if ($context === null) {
            $context = stream_context_create();
        }

        $context = FileGetContents::setHttpHeaders($context);
    }

    $return = file_get_contents($filename, $use_include_path, $context);

    if (isset($http_response_header)) {
        FileGetContents::setLastResponseHeaders($http_response_header);
    }

    return $return;
}

/**
 * Fetches all the headers sent by the server in response to a HTTPS request triggered by `get_contents()`.
 *
 * @return array|null Returns an indexed or associative array with the headers, or `null` on failure or if no request
 *                    has been made yet.
 */
function get_headers()
{
    return FileGetContents::getLastResponseHeaders();
}

/**
 * @param array $headers An indexed or associative array.
 */
function set_headers(array $headers)
{
    FileGetContents::setNextRequestHeaders($headers);
}
