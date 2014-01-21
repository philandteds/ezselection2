<?php

//
// Definition of eZSelection2Type class
//
// Created on: <8-Apr-2009 16:28:01 pf>
//
// SOFTWARE NAME: eZ Publish
// SOFTWARE RELEASE: 4.2.0
// BUILD VERSION: 22993
// COPYRIGHT NOTICE: Copyright (C) 1999-2008 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//

/*!
  \class eZStringType ezselection2type.php
  \ingroup eZDatatype
  \brief A datatype for handling content selections

  Thanks to Hans Melis and SCK-CEN (Belgian Nuclear Research Centre) for contributing their Enhanced Selection datatype.
  Thanks to Tony Wood at VisionWT for contributing their SimpleSelection datatype.

  More to come.
*/

include_once( 'kernel/common/i18n.php' );

class eZSelection2Type extends eZDataType
{
    const DATATYPESTRING = 'ezselection2';
    const CLASS_STORAGE_XML = 'data_text5';

    function eZSelection2Type()
    {
        $this->eZDataType( self::DATATYPESTRING,
                           ezpI18n::tr( 'extension/ezselection2/datatypes', 'Selection 2', 'Datatype name' ),
                           array( 'serialize_supported' => true,
                                  'object_serialize_map' => array( 'data_text' => 'selection' )
                                )
                         );
    }

    function initializeObjectAttribute( $contentObjectAttribute, $currentVersion, $originalContentObjectAttribute )
    {

        if ( $currentVersion != false )
        {
            $dataText = $originalContentObjectAttribute->attribute( "data_text" );
            $contentObjectAttribute->setAttribute( "data_text", $dataText );
        }
        else
        {
            $contentClassAttribute = $contentObjectAttribute->contentClassAttribute();
            $content = $contentClassAttribute->content();

            $defaults = array();
            foreach ($content['options'] as $options)
            {
                if (strlen($options['value']) > 0)
                {
                    $defaults[] = $options['identifier'];
                }
               
            }

            // If there are defaults set the identifier array.
            if ( count($defaults) > 0 )
            {
                $contentObjectAttribute->setContent( $defaults );
            }
        }
    }

    function validateClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        return eZInputValidator::STATE_ACCEPTED;
    }

    function fetchClassAttributeHTTPInput( $http, $base, $classAttribute )
    {
        $content = $classAttribute->content();
        $id = $classAttribute->attribute( 'id' );

        $nameArrayName = join( '_', array( $base, self::DATATYPESTRING.'_name', $id ) );
        $identifierArrayName = join( '_', array( $base, self::DATATYPESTRING.'_identifier', $id ) );
        $optionsName = join( '_', array( $base, self::DATATYPESTRING.'_options2', $id ) );

        if( $http->hasPostVariable( $nameArrayName ) || $http->hasPostVariable( $optionsName ) )
        {
            $valueArrayName = join( '_', array( $base, self::DATATYPESTRING.'_value', $id ) );
    
            $checkboxName = join( '_', array( $base, self::DATATYPESTRING.'_checkbox', $id ) );
            $multiSelectName = join( '_', array( $base, self::DATATYPESTRING.'_multi', $id ) );
            $delimiterName = join( '_', array( $base, self::DATATYPESTRING.'_delimiter', $id ) );

            $nameArray = $http->postVariable( $nameArrayName );

            $identifierArray = $http->postVariable( $identifierArrayName );
            $valueArray = $http->postVariable( $valueArrayName );

            if( $http->hasPostVariable( $optionsName ) )
            {
                $explodedOptions = explode(",",  $http->postVariable( $optionsName ));
                if (!empty($explodedOptions[0]))
                {
                    $nameArray =  array_merge ( $nameArray, $explodedOptions);
                }
            }

            if (!empty($nameArray))
            {
                // Clear the options each time.
                $content['options'] = array();

                foreach( $nameArray as $index => $name )
                {
                    $arrayName = empty ( $name ) ? "" : $name;
                    $value = empty ( $valueArray[$index]) ? "" : $valueArray[$index];

                    if( empty( $identifierArray[$index] ) )
                    {
                        $identifier = $this->generateIdentifier( $arrayName, $identifierArray );
                    }
                    else 
                    {
                        $identifier = $identifierArray[$index];
                    }

                    $content['options'][$index] = array( 'name' => $arrayName,
                                                         'identifier' => $identifier,
                                                         'value' => $value );
                }
            }

            $content['is_multiselect'] = $http->hasPostVariable( $multiSelectName ) ? 1 : 0;
            $content['is_checkbox'] = $http->hasPostVariable( $checkboxName ) ? 1 : 0;

            if( $http->hasPostVariable( $delimiterName ) )
            {
                $content['delimiter'] = $http->postVariable( $delimiterName );
            }

            $classAttribute->setContent( $content );
            $classAttribute->store();
        }

        return true;
    }

    function classAttributeContent( $classAttribute )
    {
        $xmlString = $classAttribute->attribute( self::CLASS_STORAGE_XML );

        $content = $this->xmlToClassContent( $xmlString );

        $http = eZHTTPTool::instance();

        return $content;
    }

    function storeClassAttribute( $classAttribute, $version )
    {
        $content = $classAttribute->content();

        $xmlString = $this->classContentToXml( $content );

        $classAttribute->setAttribute( self::CLASS_STORAGE_XML, $xmlString );
    }

    function validateObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $status = $this->validateAttributeHTTPInput( $http, $base, $contentObjectAttribute, false );

        return $status;
    }

    function fetchObjectAttributeHTTPInput( $http, $base, $contentObjectAttribute )
    {
        $id = $contentObjectAttribute->attribute( 'id' );
        $classContent = $contentObjectAttribute->classContent();
        $content = $contentObjectAttribute->content();

        $selectionName = join( '_', array( $base, self::DATATYPESTRING.'_selection', $id ) );

        if( $http->hasPostVariable( $selectionName ) )
        {
            $selection = $http->postVariable( $selectionName );

            $content = $selection;
        }

        $contentObjectAttribute->setContent( $content );

        return true;
    }

    function objectAttributeContent( $contentObjectAttribute )
    {
        $content = array();
        $contentString = $contentObjectAttribute->attribute( 'data_text' );

        if( !empty( $contentString ) )
        {
            $content = unserialize( $contentString );
        }

        return $content;
    }

    function hasObjectAttributeContent( $contentObjectAttribute )
    {
        $contentString = $contentObjectAttribute->attribute( 'data_text' );

        if( empty( $contentString ) )
        {
            return false;
        }

        $selection = unserialize( $contentString );

        return !empty($selection);
    }

    function storeObjectAttribute( $objectAttribute )
    {
        $content = $objectAttribute->content();

        $contentString = serialize( $content );

        $objectAttribute->setAttribute( 'data_text', $contentString );
    }

    function validateCollectionAttributeHTTPInput( $http, $base, $objectAttribute )
    {
        $status = $this->validateAttributeHTTPInput( $http, $base, $objectAttribute, true );

        return $status;
    }

    function fetchCollectionAttributeHTTPInput( $collection, $collectionAttribute, $http, $base, $objectAttribute )
    {
        $id = $objectAttribute->attribute( 'id' );
        $classContent = $objectAttribute->classContent();
        $content = $objectAttribute->content();
        $nameArray = array();

        $selectionName = join( '_', array( $base, self::DATATYPESTRING.'_selection', $id ) );
        $selection = $http->postVariable( $selectionName );

        if( $http->hasPostVariable( $selectionName ) )
        {
            $selection = $http->postVariable( $selectionName );

            if( count( $selection ) > 0 )
            {
                $options = $classContent['options'];

                foreach( $options as $option )
                {
                    if( in_array( $option['identifier'], $selection ) )
                    {
                        $nameArray[] = $option['name'];
                    }
                }
            }
        }

        $delimiter = $classContent['delimiter'];

        if( empty( $delimiter ) )
        {
            $delimiter = ',';
        }

        $dataText = join( $delimiter, $nameArray );

        $collectionAttribute->setAttribute( 'data_text', $dataText );

        return true;
    }

    function metaData( $contentObjectAttribute )
    {
        $content = $contentObjectAttribute->content();
        $classContent = $contentObjectAttribute->classContent();

        $metaDataArray = "";
        if( count( $content ) > 0 )
        {
            $options = $classContent['options'];

            foreach( $options as $option )
            {
                if( in_array( $option['identifier'], $content ) )
                {
                    $metaDataArray .= $option['name']." ";
                }
            }

            return $metaDataArray;
        }

        return "";
    }

    function title( $contentObjectAttribute, $name = null )
    {
        $content = $contentObjectAttribute->content();
        $classContent = $contentObjectAttribute->classContent();
        $titleArray = array();
        $titleString = "";

        if( count( $content ) > 0 )
        {
            $options = $classContent['options'];

            foreach( $options as $option )
            {
                if( in_array( $option['identifier'], $content ) )
                {
                    $titleArray[] = $option['name'];
                }
            }

        }

        if( count( $titleArray ) > 0 )
        {
            $delimiter = $classContent['delimiter'];

            if( empty( $delimiter ) )
            {
                $delimiter = ",";
            }

            $titleString = join( $delimiter, $titleArray );
        }

        return $titleString;
    }

    function isIndexable()
    {
        return true;
    }

    function isInformationCollector()
    {
        return true;
    }

    function sortKey( $objectAttribute )
    {
        $content = $objectAttribute->content();
        $contentString = join(' ', $content);
        $contentString = strtolower( $contentString );

        return $contentString;
    }

    function sortKeyType()
    {
        return 'string';
    }

    function serializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        $doc = $attributeParametersNode->ownerDocument;

        //Populate the dom
        $this->createDOMFromContent(  $classAttribute->content() , $doc, $attributeParametersNode);
    }

    function unserializeContentClassAttribute( $classAttribute, $attributeNode, $attributeParametersNode )
    {
        $content = $this->createContentFromDOM($attributeParametersNode);

        $xmlString = $this->classContentToXml( $content );

        $classAttribute->setAttribute( self::CLASS_STORAGE_XML, $xmlString );
    }

    function toString( $contentObjectAttribute )
    {
        $content = $contentObjectAttribute->content();
        
        return implode( "|", $content);
    }

    function fromString( $contentObjectAttribute, $string )
    {
        if ( $string == '' )
            return true;

        $contentString = serialize(explode( '|', $string ));

        $contentObjectAttribute->setAttribute( 'data_text', $contentString );
    }

    function createDOMFromContent($content, $doc, $root)
    {
        $optionsNode = $doc->createElement( 'options' );

        if( isset( $content['options'] ) and count( $content['options'] ) > 0 )
        {
            foreach( $content['options'] as $option )
            {
                $optionNode = $doc->createElement( 'option' );

                $optionNode->setAttribute( 'name', $option['name'] );
                $optionNode->setAttribute( 'identifier', $option['identifier']);
                $optionNode->setAttribute( 'value', $option['value'] );

                $optionsNode->appendChild( $optionNode );

                unset( $optionNode );
            }
        }

        $root->appendChild( $optionsNode );


        // Multiselect
        if( isset( $content['is_multiselect'] ) )
        {
            $multiSelectNode = $doc->createElement( 'multiselect', $content['is_multiselect'] );
            $root->appendChild( $multiSelectNode );
        }

        // Checkbox
        if( isset( $content['is_checkbox'] ) )
        {
            $checkboxNode = $doc->createElement( 'checkbox', $content['is_checkbox'] );
            $root->appendChild( $checkboxNode );
        }

        // Delimiter
        if( isset( $content['delimiter'] ) )
        {
            $delimiterElement = $doc->createElement('delimiter');
            $delimiterElement->appendChild( $doc->createCDATASection( $content['delimiter'] ) );
            $root->appendChild( $delimiterElement );
        }

    }

    function createContentFromDOM($dom)
    {
        $content = array();

        $delimiterNode =  $dom->getElementsByTagName( 'delimiter' )->item(0);
        $multiselectNode =  $dom->getElementsByTagName( 'multiselect' )->item(0);
        $checkboxNode =  $dom->getElementsByTagName( 'checkbox' )->item(0);

        $delimiter = $delimiterNode ? $delimiterNode->nodeValue : "";
        $multiselect = $multiselectNode ? $multiselectNode->textContent : 0;
        $checkbox = $checkboxNode ? $checkboxNode->nodeValue : 0;

        $content['delimiter'] = $delimiter;
        $content['is_multiselect'] = intval($multiselect);
        $content['is_checkbox'] = intval($checkbox);
        $content['options'] = array();

        $optionsNode = $dom->getElementsByTagName( 'options' )->item(0);
  
        if( $optionsNode instanceof DomElement && $optionsNode->hasChildNodes() === true )
        {
            $children = $optionsNode->childNodes;

            foreach( $children as $key => $child )
            {
                if( $child instanceof DomElement)
                {
                    $content['options'][] = array( 'name' => $child->getAttribute( 'name' ),
                                                   'identifier' => $child->getAttribute( 'identifier' ),
                                                   'value' => $child->getAttribute( 'value' ) );
                }
            }
        }

        return $content;
    }

    function classContentToXml( $content )
    {
        $doc = new DOMDocument();
        $root = $doc->createElement( 'content' );

        $this->createDOMFromContent($content, $doc, $root);

        $doc->appendChild( $root );

        $xml = $doc->saveXML();

        return $xml;
    }

    function xmlToClassContent( $xmlString )
    {
        $content = array();

        if( $xmlString != '')
        {
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->loadXML( $xmlString );

            if( $dom )
            {
                $content = $this->createContentFromDOM($dom);
            }
            else
            {
                $content['options'] = array();
                $content['is_multiselect'] = 0;
                $content['delimiter'] = '';
            }
        }
   
        return $content;
    }    

    function generateIdentifier( $name, $identifierArray = array() )
    {
        if( empty( $name ) )
        {
            return '';
        }

        $identifier = $name;

        $trans = eZCharTransform::instance();
        $generatedIdentifier = $trans->transformByGroup( $identifier, 'identifier' );

        // We have $generatedIdentifier now, check for existance
        if( is_array( $identifierArray ) and
            count( $identifierArray ) > 0 and
            in_array( $generatedIdentifier, $identifierArray ) )
        {
            $highestNumber = 0;

            foreach( $identifierArray as $ident )
            {
                if( preg_match( '/^' . $generatedIdentifier . '__(\d+)$/', $ident, $matchArray ) )
                {
                    if( $matchArray[1] > $highestNumber )
                    {
                        $highestNumber = $matchArray[1];
                    }
                }
            }

            $generatedIdentifier .= "__" . ++$highestNumber;
        }

        return $generatedIdentifier;
    }

    function validateAttributeHTTPInput( $http, $base, $contentObjectAttribute, $isInformationCollection = false )
    {
        $id = $contentObjectAttribute->attribute( 'id' );
        $classAttribute = $contentObjectAttribute->contentClassAttribute();
        $classContent = $classAttribute->content();
        $isRequired = false;
        $infoCollectionCheck = ( $isInformationCollection == $classAttribute->attribute( 'is_information_collector' ) );

        $isRequired = $contentObjectAttribute->validateIsRequired();

        $selectionName = join( '_', array( $base, self::DATATYPESTRING.'_selection', $id ) );

        if( $http->hasPostVariable( $selectionName ) )
        {
            $selection = $http->postVariable( $selectionName );

            if( $infoCollectionCheck === true )
            {
                switch( true )
                {
                    case $isRequired === true and count( $selection ) == 0:
                    case $isRequired === true and count( $selection ) == 1 and empty( $selection[0] ):
                    {
                        $contentObjectAttribute->setValidationError( ezpI18n::tr( 'extension/ezselection2/datatypes',
                                                                             'This is a required field.' )
                                                                   );
                        return eZInputValidator::STATE_INVALID;
                    } break;
                }
            }
        }
        else
        {
            if( $infoCollectionCheck === true and $isRequired === true )
            {
                $contentObjectAttribute->setValidationError( ezpI18n::tr( 'extension/ezselection2/datatypes',
                                                                     'No POST variable. Please check your configuration.' )
                                                           );
            }
            else
            {
                return eZInputValidator::STATE_ACCEPTED;
            }

            return eZInputValidator::STATE_INVALID;
        }

        return eZInputValidator::STATE_ACCEPTED;
    }
}

eZDataType::register( eZSelection2Type::DATATYPESTRING, "ezselection2type" );
?>