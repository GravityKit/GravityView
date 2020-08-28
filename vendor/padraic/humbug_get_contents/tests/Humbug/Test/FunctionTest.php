<?php

/*
 * This file is part of the Humbug package.
 *
 * (c) 2015 Pádraic Brady <padraic.brady@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Humbug\Test;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class FunctionTest extends TestCase
{
    private static $result;

    public function setup()
    {
        vfsStream::setup('home_root_path');
        if (null === self::$result) {
            $result = humbug_get_contents('https://www.howsmyssl.com/a/check');
            self::$result = json_decode($result, true);
        }
    }

    public function teardown()
    {
        self::$result = null;
    }

    public function testRating()
    {
        $this->assertEquals('Probably Okay', self::$result['rating']);
    }

    public function testTlsCompression()
    {
        $this->assertFalse(self::$result['tls_compression_supported']);
    }

    public function testSslNotUsed()
    {
        $this->assertEquals(stripos(self::$result['tls_version'], 'tls 1.'), 0);
    }

    public function testBeastVulnerability()
    {
        $this->assertFalse(self::$result['beast_vuln']);
    }

    public function testInsecureCipherSuites()
    {
        $this->assertEmpty(self::$result['insecure_cipher_suites']);
    }

    public function testUnknownCipherSuites()
    {
        $this->assertFalse(self::$result['unknown_cipher_suite_supported']);
    }

    public function testFileGetContentsWillPassThrough()
    {
        file_put_contents(vfsStream::url('home_root_path/humbug.tmp'), ($expected = uniqid()));
        $this->assertEquals(file_get_contents(vfsStream::url('home_root_path/humbug.tmp')), $expected);
    }

    public function testCanGetResponseHeaders()
    {
        humbug_set_headers(array('Accept-Language: da\r\n'));
        humbug_get_contents('http://padraic.github.io');
        $this->assertTrue(count(humbug_get_headers()) > 0);
    }

    public function testCanSetRequestHeaders()
    {
        humbug_set_headers(array(
            'Accept-Language: da',
            'User-Agent: Humbug'
        ));
        $out = humbug_get_contents('http://www.procato.com/my+headers/');
        $this->assertEquals(1, preg_match('%'.preg_quote('<th>Accept-Language</th><td>da</td>').'%', $out));
        $this->assertEquals(1, preg_match('%'.preg_quote('<th>User-Agent</th><td>Humbug</td>').'%', $out));
    }
}
