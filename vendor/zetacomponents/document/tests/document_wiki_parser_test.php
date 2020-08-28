<?php
/**
 * ezcDocumentConverterEzp3TpEzp4Tests
 * 
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package Document
 * @version //autogen//
 * @subpackage Tests
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentWikiParserTests extends ezcTestCase
{
    protected static $testDocuments = null;

    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public static function getTestDocuments()
    {
        if ( self::$testDocuments === null )
        {
            // Get a list of all test files from the respektive folder
            $testFiles = glob( dirname( __FILE__ ) . '/files/wiki/*/*.txt' );

            // Create array with the test file and the expected result file
            foreach ( $testFiles as $file )
            {
                self::$testDocuments[] = array(
                    $file,
                    substr( $file, 0, -3 ) . 'ast'
                );
            }
        }

        return self::$testDocuments;
        return array_slice( self::$testDocuments, 19, 1 );
    }

    /**
     * @dataProvider getTestDocuments
     */
    public function testParseFile( $from, $to )
    {
        if ( !is_file( $to ) )
        {
            $this->markTestSkipped( "Comparision file '$to' not yet defined." );
        }

        $type           = ucfirst( basename( dirname( $from ) ) );
        $tokenizerClass = 'ezcDocumentWiki' . $type . 'Tokenizer';

        $tokenizer = new $tokenizerClass();
        $parser    = new ezcDocumentWikiParser();
        $parser->options->errorReporting = E_PARSE | E_ERROR | E_WARNING;
        $ast       = $parser->parse( $tokenizer->tokenizeFile( $from ) );

        $expected = include $to;

        // Store test file, to have something to compare on failure
        $tempDir = $this->createTempDir( 'wiki_parser_' . $type . '_' ) . '/';
        file_put_contents( $tempDir . basename( $to ), "<?php\n\nreturn " . var_export( $ast, true ) . ";\n\n" );

        $this->assertEquals(
            $expected,
            $ast,
            'Extracted ast do not match expected ast.'
        );

        // Remove tempdir, when nothing failed.
        $this->removeTempDir();
    }
}

?>
