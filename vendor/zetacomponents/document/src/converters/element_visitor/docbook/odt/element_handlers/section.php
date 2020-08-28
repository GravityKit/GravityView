<?php
/**
 * File containing the ezcDocumentDocbookToOdtSectionHandler class.
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
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @access private
 */

/**
 * Visit docbook sections.
 *
 * Visit docbook <section/> and transform them into ODT <text:section/>. 
 * Handles <title/> nodes in addition.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentDocbookToOdtSectionHandler extends ezcDocumentDocbookToOdtBaseHandler
{
    /**
     * Current section nesting level in the docbook document.
     *
     * @var int
     */
    protected $level = 0;

    /**
     * Last auto-generated section ID.
     * 
     * @var int
     */
    protected $lastSectionId = 0;

    /**
     * Handle a node
     *
     * Handle / transform a given node, and return the result of the
     * conversion.
     *
     * @param ezcDocumentElementVisitorConverter $converter
     * @param DOMElement $node
     * @param mixed $root
     * @return mixed
     */
    public function handle( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        switch ( $node->localName )
        {
            case 'section':
                $this->handleSection( $converter, $node, $root );
                break;
            case 'title':
                $this->handleTitle( $converter, $node, $root );
                break;
            case 'sectioninfo':
                // @todo
                break;
        }

        return $root;
    }

    /**
     * Handles the <title/> element.
     * 
     * @param ezcDocumentElementVisitorConverter $converter 
     * @param DOMElement $node 
     * @param mixed $root
     */
    protected function handleTitle( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $h = $root->appendChild(
            $root->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_TEXT,
                'text:h'
            )
        );
        $h->setAttributeNS(
            ezcDocumentOdt::NS_ODT_TEXT,
            'text:outline-level',
            $this->level
        );
        $this->createRefMark( $h, $node );

        $this->styler->applyStyles( $node, $h );

        $converter->visitChildren( $node, $h );
    }


    /**
     * createRefMark 
     * 
     * @param mixed $h 
     * @param mixed $node 
     * @return void
     */
    protected function createRefMark( $h, $node )
    {
        $nodeParent = $node->parentNode;
        if ( $nodeParent !== null
             && $nodeParent->nodeType === XML_ELEMENT_NODE
             && ( $nodeParent->hasAttribute( 'id' ) || $nodeParent->hasAttribute( 'ID' ) )
           )
        {
            $ref = $h->appendChild(
                $h->ownerDocument->createElementNS(
                    ezcDocumentOdt::NS_ODT_TEXT,
                    'text:reference-mark'
                )
            );
            $ref->setAttributeNS(
                ezcDocumentOdt::NS_ODT_TEXT,
                'text:name',
                ( $nodeParent->hasAttribute( 'id' ) ? $nodeParent->getAttribute( 'id' ) : $nodeParent->getAttribute( 'ID' ) )
            );
        }
    }

    /**
     * Handles the <section/> element.
     * 
     * @param ezcDocumentElementVisitorConverter $converter 
     * @param DOMElement $node 
     * @param mixed $root
     */
    protected function handleSection( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        ++$this->level;

        $section = $root->appendChild(
            $root->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_TEXT,
                'text:section'
            )
        );
        $section->setAttributeNS(
            ezcDocumentOdt::NS_ODT_TEXT,
            'text:name',
            ( $node->hasAttribute( 'ID' )
                ? $node->getAttribute( 'ID' )
                : $this->generateId()
            )
        );
        
        $converter->visitChildren( $node, $section );

        --$this->level;
    }

    /**
     * Generates a section ID.
     * 
     * @return string
     */
    protected function generateId()
    {
        return 'ezcDocumentSectionId' . ++$this->lastSectionId;
    }
}

?>
