<?php
/**
 * File containing the ezcDocumentXhtmlImageElementFilter class
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
 * Filter for XHtml images.
 *
 * Filter HTML image elements, and try to find optional captions
 * belonging to the image, and alt tags. Transforming the images into
 * correct media objects depending wheather they are inlined or not.
 *
 * @package Document
 * @version //autogen//
 * @access private
 */
class ezcDocumentXhtmlImageElementFilter extends ezcDocumentXhtmlElementBaseFilter
{
    /**
     * Filter a single element
     *
     * @param DOMElement $element
     * @return void
     */
    public function filterElement( DOMElement $element )
    {
        if ( !$element->hasAttribute( 'src' ) )
        {
            // If there is no actual file referenced, we have nothing to do.
            return;
        }

        if ( $this->isInline( $element ) )
        {
            // Image inline in text.
            $element->setProperty( 'type', 'inlinemediaobject' );
        }
        else
        {
            $element->setProperty( 'type', 'mediaobject' );
        }

        // Create the descendant nodes
        $imageObject = new ezcDocumentPropertyContainerDomElement( 'span' );
        $element->appendChild( $imageObject );
        $imageObject->setProperty( 'type', 'imageobject' );

        $imageData = new ezcDocumentPropertyContainerDomElement( 'span' );
        $imageObject->appendChild( $imageData );
        $imageData->setProperty( 'type', 'imagedata' );
        $attributes = array(
            'fileref' => $element->getAttribute( 'src' ),
        );

        // Keep optionally specified image dimensions
        if ( $element->hasAttribute( 'width' ) )
        {
            $attributes['width'] = $element->getAttribute( 'width' );
        }

        if ( $element->hasAttribute( 'height' ) )
        {
            $attributes['depth'] = $element->getAttribute( 'height' );
        }

        // Store attributes for element
        $imageData->setProperty( 'attributes', $attributes );

        // Check if there is a parent node, which may be some kind of wrapping
        // element of the image and its caption.
        if ( ( $element->parentNode->tagName === 'div' ) &&
             ( $text = trim( $this->extractText( $element->parentNode ) ) ) )
        {
            // Create the docbook caption node structure
            $textObject = new ezcDocumentPropertyContainerDomElement( 'span' );
            $element->appendChild( $textObject );
            $textObject->setProperty( 'type', 'caption' );

            $phrase = new ezcDocumentPropertyContainerDomElement( 'span', htmlspecialchars( $text ) );
            $textObject->appendChild( $phrase );
            $phrase->setProperty( 'type', 'para' );
        }

        // Keep textual image annotations
        if ( $element->hasAttribute( 'alt' ) )
        {
            $textObject = new ezcDocumentPropertyContainerDomElement( 'span' );
            $element->appendChild( $textObject );
            $textObject->setProperty( 'type', 'textobject' );

            $phrase = new ezcDocumentPropertyContainerDomElement( 'span', htmlspecialchars( $element->getAttribute( 'alt' ) ) );
            $textObject->appendChild( $phrase );
            $phrase->setProperty( 'type', 'para' );
        }
    }

    /**
     * Extract text content
     *
     * Extract and remove all textual contents from the node and its
     * descendants.
     *
     * @param DOMElement $element
     * @return string
     */
    protected function extractText( DOMElement $element )
    {
        $text = '';
        foreach ( $element->childNodes as $child )
        {
            switch ( $child->nodeType )
            {
                case XML_TEXT_NODE:
                    $text .= $child->nodeValue;
                    $child->nodeValue = '';
                    break;

                case XML_ELEMENT_NODE:
                    $text .= $this->extractText( $child );
                    break;
            }
        }

        return $text;
    }

    /**
     * Check if filter handles the current element
     *
     * Returns a boolean value, indicating weather this filter can handle
     * the current element.
     *
     * @param DOMElement $element
     * @return void
     */
    public function handles( DOMElement $element )
    {
        return ( $element->tagName === 'img' );
    }
}

?>
