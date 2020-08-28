<?php
/**
 * ezcDocTestConvertDocbookDocbook
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
class ezcDocumentXmlBaseTests extends ezcTestCase
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testLoadXmlDocumentFromFile()
    {
        $doc = new ezcDocumentDocbook();
        $doc->loadFile( 
            dirname( __FILE__ ) . '/files/xhtml_sample_basic.xml'
        );

        $this->assertTrue(
            $doc->getDomDocument() instanceof DOMDocument,
            'DOMDocument not created properly'
        );
    }

    public function testLoadXmlDocumentFromString()
    {
        $string = file_get_contents(
            dirname( __FILE__ ) . '/files/xhtml_sample_basic.xml'
        );

        $doc = new ezcDocumentDocbook();
        $doc->loadString( $string );

        $this->assertTrue(
            $doc->getDomDocument() instanceof DOMDocument,
            'DOMDocument not created properly'
        );
    }

    public function testLoadErroneousXmlDocument()
    {
        $doc = new ezcDocumentDocbook();

        try
        {
            $doc->loadFile( 
                dirname( __FILE__ ) . '/files/xhtml_sample_errnous.xml'
            );
        }
        catch ( ezcDocumentErroneousXmlException $e )
        {
            $errors = $e->getXmlErrors();

            $this->assertSame(
                2,
                count( $errors ),
                'Expected 2 XML errors.'
            );
        }

        $this->assertTrue(
            $doc->getDomDocument() instanceof DOMDocument,
            'DOMDocument not created properly'
        );
    }

    public function testLoadErroneousXmlDocumentSilent()
    {
        $doc = new ezcDocumentDocbook();
        $doc->options->failOnError = false;
        $doc->loadFile( 
            dirname( __FILE__ ) . '/files/xhtml_sample_errnous.xml'
        );

        $this->assertTrue(
            $doc->getDomDocument() instanceof DOMDocument,
            'DOMDocument not created properly'
        );
    }

    public function testSerializeXml()
    {
        $doc = new ezcDocumentDocbook();
        $doc->loadFile( 
            dirname( __FILE__ ) . '/files/xhtml_sample_basic.xml'
        );

        $this->assertEquals(
            file_get_contents( dirname( __FILE__ ) . '/files/xhtml_sample_basic.xml' ),
            $doc->save()
        );
    }

    public function testSerializeXmlFormat()
    {
        $doc = new ezcDocumentDocbook();
        $doc->options->indentXml = true;
        $doc->loadFile( 
            dirname( __FILE__ ) . '/files/xhtml_sample_basic.xml'
        );

        $this->assertEquals(
            file_get_contents( dirname( __FILE__ ) . '/files/xhtml_sample_basic_indented.xml' ),
            $doc->save()
        );
    }
}

?>
