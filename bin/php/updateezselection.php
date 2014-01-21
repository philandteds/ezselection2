#!/usr/bin/env php
<?php
//
// Created on: <19-Jul-2004 10:51:17 amos>
//
// SOFTWARE NAME: eZ Publish
// SOFTWARE RELEASE: 4.0.3
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
require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "Update eZSelection\n" .
                                                        "Updgrade ezselection datatype to ezselection2\n" .
                                                        "\n" .
                                                        "./bin/php/upgradeezselection.php " ),
                                     'use-session' => false, 
                                     'debug-output' => true,
                                    'use-modules' => false,
                                     'use-extensions' => true ) );

$script->startup();

$sys = eZSys::instance();

$options = $script->getOptions();

$script->initialize();

$cli->output( "Examining class attributes" );

$db = eZDB::instance();

// First we examine all class attributes for any 'ezselection' attributes
$classAttributes = $db->arrayQuery("select id from ezcontentclass_attribute where data_type_string = 'ezselection';");

if (count($classAttributes) > 0)
{
    $cli->output( "Found ".count($classAttributes) );

    foreach ($classAttributes as $index => $classAttributeArray)
    {
        $cli->output( "Upgrading attribute ".++$index  );

        $classAttributeID = $classAttributeArray['id'];
        $classAttribute = eZContentClassAttribute::fetch($classAttributeID);
        $classContent = $classAttribute->attribute('content');

        // First upgrade the class attribute
        $identifiers = array();
        foreach ($classContent['options'] as $index => $option)
        {
            // Use ezselection2 to generate identifiers based on the old name.
            $identifier = eZSelection2Type::generateIdentifier($option['name'], $identifiers);

            // Update the list of identifiers used to prevent their repeated use.    
            $identifiers[] = $identifier;

            // Reset 
            $classContent['options'][$index]['identifier'] = $identifier;
            $classContent['options'][$index]['value'] = "";

            // Remove the id
            unset($classContent['options'][$index]['id']);
        }

        $classContent['is_checkbox'] = "";
        $classContent['delimiter'] = "";

        // Next upgrade the object attribute
        $objectAttributes = eZContentObjectAttribute::fetchSameClassAttributeIDList($classAttributeID);
        foreach ($objectAttributes as $objectAttribute)
        {
            $objectContent = $objectAttribute->attribute('content');
            $objectIdentifiers = array();
            foreach ($objectContent as $optionID)
            {
                // Ensure there is an option. Some objects may have empty selections
                if (isset($classContent['options'][$optionID]))
                {
                    $objectIdentifiers[] = $classContent['options'][$optionID]['identifier'];
                }
            }
 
            $objectAttributeID = $objectAttribute->attribute('id');

            // Set the corret content for this attribute. We can't use ezselection2 because the object attribute is tied to the old attribute.
            $query = "update ezcontentobject_attribute set data_text = '".serialize($objectIdentifiers)."' where id = $objectAttributeID";
            $db->query($query);
        }

        // Update the class attribute with the new content
        $doc = new DOMDocument();
        $root = $doc->createElement( 'content' );
        $doc->appendChild( $root );
        eZSelection2Type::createDOMFromContent($classContent, $doc, $root);
        $xml = $doc->saveXML();
        $query = "update ezcontentclass_attribute set ".eZSelection2Type::CLASS_STORAGE_XML." = '$xml' where id = $classAttributeID";
        $db->query($query);

        // Reset the multiselect field to blank
        $query = "update ezcontentclass_attribute set data_int1 = '' where id = $classAttributeID";
        $db->query($query);

        // Finally update the datatype string
        $query = "update ezcontentclass_attribute set data_type_string = '".eZSelection2Type::DATATYPESTRING."' where id = $classAttributeID";
        $db->query($query);
   }

}
else
{
    $cli->output( "There are no ezselection attributes to upgrade" );
}

$cli->output( "Finished ezselection2 upgrade" );

$script->shutdown();

?>
