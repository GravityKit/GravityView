<?php
/**
 * File containing the ezcDocumentDocbookToOdtTableHandler class.
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
 * Visit tables.
 *
 * Visit docbook <table/> and child elements and transform them into ODT <table:table/> 
 * and corresponding child elements.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentDocbookToOdtTableHandler extends ezcDocumentDocbookToOdtBaseHandler
{
    /**
     * Maps table element to handling methods in this class.
     * 
     * @var array(string=>string)
     */
    protected $methodMap = array(
        'table'   => 'handleTable',
        'caption' => 'handleCaption',
        'thead'   => 'handleThead',
        'tbody'   => 'handleTbody',
        'tr'      => 'handleTr',
        'td'      => 'handleTd',
        // Old style DocBook tables
        'row'     => 'handleTr',
        'entry'   => 'handleTd'
    );

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
        if ( !isset( $this->methodMap[$node->localName] ) )
        {
            // This only occurs when the handler is assigned to an unknown 
            // node, which should not happen at all.
            throw new ezcDocumentMissingVisitorException( $node->localName );
        }

        $method = $this->methodMap[$node->localName];

        return $this->$method( $converter, $node, $root );
    }

    /**
     * Handles the table base element.
     * 
     * @param ezcDocumentElementVisitorConverter $converter 
     * @param DOMElement $node 
     * @param mixed $root 
     * @return mixed
     */
    protected function handleTable( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $table = $root->appendChild(
            $root->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_TABLE,
                'table:table'
            )
        );

        $this->createCellDefinition( $node, $table );

        $this->styler->applyStyles( $node, $table );

        $converter->visitChildren( $node, $table );
        return $root;
    }

    /**
     * Creates the ODT cell defintions.
     * 
     * @param DOMElement $docBookTable 
     * @param DOMElement $odtTable 
     * @return void
     */
    protected function createCellDefinition( $docBookTable, $odtTable )
    {
        $rows = $docBookTable->getElementsByTagName( 'tr' );

        // No XHTML style rows found, look for old style rows
        if ( $rows->length === 0 )
        {
            $rows = $docBookTable->getElementsByTagName( 'row' );
        }
        
        if ( $rows->length !== 0 )
        {
            $firstRow = $rows->item( 0 );
            foreach ( $firstRow->childNodes as $cell )
            {
                if ( $cell->nodeType !== XML_ELEMENT_NODE
                     || ( $cell->localName !== 'td' && $cell->localName !== 'th' && $cell->localName !== 'entry' )
                )
                {
                    continue;
                }
                $count = ( $cell->hasAttribute( 'colspan' ) ? (int) $cell->getAttribute( 'colspan' ) : 1 );
                for ( $i = 0; $i < $count; ++$i )
                {
                    $odtCell = $odtTable->appendChild(
                        $odtTable->ownerDocument->createElementNS(
                            ezcDocumentOdt::NS_ODT_TABLE,
                            'table:table-column'
                        )
                    );
                }
            }
        }

    }

    /**
     * Handles table captions.
     * 
     * @param ezcDocumentElementVisitorConverter $converter 
     * @param DOMElement $node 
     * @param mixed $root 
     * @return mixed
     */
    protected function handleCaption( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $root->setAttributeNS(
            ezcDocumentOdt::NS_ODT_TABLE,
            'table:name',
            $node->nodeValue
        );
        return $root;
    }

    /**
     * Handles table headers.
     * 
     * @param ezcDocumentElementVisitorConverter $converter 
     * @param DOMElement $node 
     * @param mixed $root 
     * @return mixed
     */
    protected function handleThead( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $tableHeaderRows = $root->appendChild(
            $root->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_TABLE,
                'table:table-header-rows'
            )
        );

        $this->styler->applyStyles( $node, $tableHeaderRows );

        $converter->visitChildren( $node, $tableHeaderRows );
        return $root;
    }

    /**
     * Handles table bodies.
     *
     * Simply ignores the tag, since ODT does not have table body marked up.
     * 
     * @param ezcDocumentElementVisitorConverter $converter 
     * @param DOMElement $node 
     * @param mixed $root 
     * @return mixed
     */
    protected function handleTbody( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        // Skip
        $converter->visitChildren( $node, $root );
        return $root;
    }

    /**
     * Handles table rows.
     * 
     * @param ezcDocumentElementVisitorConverter $converter 
     * @param DOMElement $node 
     * @param mixed $root 
     * @return mixed
     */
    protected function handleTr( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $tableRow = $root->appendChild(
            $root->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_TABLE,
                'table:table-row'
            )
        );

        $this->styler->applyStyles( $node, $tableRow );

        $converter->visitChildren( $node, $tableRow );
        return $root;
    }

    /**
     * Handles table cells.
     * 
     * @param ezcDocumentElementVisitorConverter $converter 
     * @param DOMElement $node 
     * @param mixed $root 
     * @return mixed
     */
    protected function handleTd( ezcDocumentElementVisitorConverter $converter, DOMElement $node, $root )
    {
        $tableCell = $root->appendChild(
            $root->ownerDocument->createElementNS(
                ezcDocumentOdt::NS_ODT_TABLE,
                'table:table-cell'
            )
        );
        // @todo: Can we make this configurable somehow?
        $tableCell->setAttributeNS(
            ezcDocumentOdt::NS_ODT_OFFICE,
            'office:value-type',
            'string'
        );

        $this->styler->applyStyles( $node, $tableCell );

        // @todo: Colspan / rowspan

        $converter->visitChildren( $node, $tableCell );
        return $root;
    }
}

?>
