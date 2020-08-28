<?php
/**
 * ezcDocumentOdtFormattingPropertiesTest.
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

require_once 'property_generator_test.php';

/**
 * Test suite for class.
 * 
 * @package Document
 * @subpackage Tests
 */
class ezcDocumentOdtStyleTextPropertyGeneratorTest extends ezcDocumentOdtStylePropertyGeneratorTest
{
    public static function suite()
    {
        return new PHPUnit_Framework_TestSuite( __CLASS__ );
    }

    public function testCtor()
    {
        $gen = new ezcDocumentOdtStyleTextPropertyGenerator(
            $this->styleConverters
        );

        $this->assertAttributeSame(
            $this->styleConverters,
            'styleConverters',
            $gen
        );

        $this->assertAttributeEquals(
            array(
                'text-decoration',
                'font-size',
                'font-name',
                'font-weight',
                'color',
                'background-color',
            ),
            'styleAttributes',
            $gen
        );
    }

    public function testCreateProperty()
    {
        $gen = new ezcDocumentOdtStyleTextPropertyGenerator(
            $this->styleConverters
        );
        $parent = $this->getDomElementFixture();

        $gen->createProperty(
            $parent,
            array(
                'font-name' => new ezcDocumentPcssStyleStringValue( 'Font Name' ),
            )
        );

        $this->assertPropertyExists(
            ezcDocumentOdt::NS_ODT_STYLE,
            'text-properties',
            array(
                array( ezcDocumentOdt::NS_ODT_STYLE, 'font-name' )
            ),
            $parent
        );
    }
}

?>
