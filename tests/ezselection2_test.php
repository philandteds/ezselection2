<?php

class eZSelection2Test extends ezpDatatypeTypeTestCase
{
	public function __construct()
    {
        parent::__construct();
        $this->setName( "eZSelection2 Unit Tests" );        
    }
	
    protected function setUp()
    {    	
    	parent::setUp();
  
        // Enforce new db per test
        eZSelection2TestSuite::setUpDatabase();
    }

    /*
     * Test the system setup for this datatype.
     */
    public function testExists()
    {
        // Fetch simple example
        $example = eZSelection2TestSuite::createSelection();
   
        // Simple test for existance.
        self::assertEquals(strtolower($example['objectAttribute']->attribute("contentclass_attribute_identifier")), 'ezselection2');

    }
    
    /*
     * Test the datatype content
     *
     * Tests classattribute->content
     */
    public function testClassAttributeContent()
    {
        $example = eZSelection2TestSuite::createSelection();

        $classContent = $example['classAttribute']->attribute('content');

        // Simple test for existance.
        self::assertTrue(empty($classContent['delimiter']));
        self::assertTrue(empty($classContent['is_multiselect']));
        self::assertTrue(empty($classContent['query']));
        self::assertTrue(empty($classContent['is_checkbox']));
        self::assertTrue(empty($classContent['options']));
    }

    public function testReadingAndCreatingClassContent()
    {
        $example = eZSelection2TestSuite::createSelection();

        $exampleXML = eZSelection2TestSuite::fetchXMLSelection();

        $newContent = $example['datatype']->xmlToClassContent( $exampleXML );

        $example['classAttribute']->setContent( $newContent );
        $example['classAttribute']->store();
        $example['classAttribute']->storeDefined();

        // Test this content through the datatype methods
        $classContent =  $example['classAttribute']->attribute('content');

        self::assertTrue(empty($classContent['delimiter']));
        self::assertEquals($classContent['is_multiselect'], 1);
        self::assertTrue(empty($classContent['query']));
        self::assertEquals($classContent['is_checkbox'], 1);

        // Assert that all options are correct
        foreach( $classContent['options'] as $index => $option )
        {
            self::assertEquals($option['name'], "Option".($index+1));
            self::assertEquals($option['identifier'], "option".($index+1));
        }

        // Test the xml generation and integrity.
        $contentXml = $example['datatype']->classContentToXml($classContent);
        $dom = new DomDocument;
        self::assertTrue( $dom->loadXML($contentXml), "Unable to load doc xml into dom");
    }

    public function testFetchClassAttributeHTTPInput()
    {
        $example = eZSelection2TestSuite::createSelection();

        // Set various post variables
        $http = eZHTTPTool::instance();        
        $classAttributeID = $example['classAttribute']->attribute( 'id' );

        $http->setPostVariable("ContentClass_ezselection2_multi_".$classAttributeID, 1);
        $http->setPostVariable("ContentClass_ezselection2_checkbox_".$classAttributeID, 1);
        $http->setPostVariable("ContentClass_ezselection2_delimiter_".$classAttributeID, "");
        $http->setPostVariable("ContentClass_ezselection2_name_".$classAttributeID, array("Option1", "Option2", "Option3","Option4","Option5"));
        $http->setPostVariable("ContentClass_ezselection2_identifier_".$classAttributeID, array("option1", "option2", "option3","option4","option5"));
        $http->setPostVariable("ContentClass_ezselection2_values_".$classAttributeID, array("", "", "","",""));

        $example['datatype']->fetchClassAttributeHTTPInput( $http, "ContentClass", $example['classAttribute'] );

        $classContent = $example['classAttribute']->attribute('content');

        self::assertTrue(empty($classContent['delimiter']));
        self::assertEquals($classContent['is_multiselect'], 1);
        self::assertTrue(empty($classContent['query']));
        self::assertEquals($classContent['is_checkbox'], 1);

        // Assert that all options are correct
        foreach( $classContent['options'] as $index => $option )
        {
            self::assertEquals($option['name'], "Option".($index+1));
            self::assertEquals($option['identifier'], "option".($index+1));
        }
    }
  
    public function testValidateCollectionAttributeHTTPInput()
    {
        $example = eZSelection2TestSuite::createSelection();

        $http = eZHTTPTool::instance();        
        $objectAttributeID = $example['objectAttribute']->attribute( 'id' );

        // Set a value and test.
        $http->setPostVariable("ContentObjectAttribute_ezselection2_selection_".$objectAttributeID, array("selection"));

        $inputParameters = null;
        $example['objectAttribute']->validateInformation($http, "ContentObjectAttribute" ,$inputParameters);

        $valid = $example['datatype']->validateCollectionAttributeHTTPInput( $http, "ContentObjectAttribute", $example['objectAttribute']);
        self::assertEquals( $valid, eZInputValidator::STATE_ACCEPTED);

        // Set an empty value and test again
        unset($_POST["ContentObjectAttribute_ezselection2_selection_".$objectAttributeID]);
        $example['objectAttribute']->validateInformation($http, "ContentObjectAttribute" ,$inputParameters);

        $valid = $example['datatype']->validateCollectionAttributeHTTPInput( $http, "ContentObjectAttribute", $example['objectAttribute']);

        self::assertEquals( $valid, eZInputValidator::STATE_INVALID);

        // More tests could be done with additional classAttributes
    }

    public function testFetchObjectAttributeHTTPInput()
    {
        $example = eZSelection2TestSuite::createSelection();

        self::assertFalse($example['objectAttribute']->attribute('has_content'));

        // Set various post variables
        $http = eZHTTPTool::instance();        
        $objectAttributeID = $example['objectAttribute']->attribute( 'id' );

        $testContent = array("test");

        $http->setPostVariable("ContentObject_ezselection2_selection_".$objectAttributeID, $testContent);

        // Store the value
        $example['datatype']->fetchObjectAttributeHTTPInput( $http, "ContentObject", $example['objectAttribute'] );
        $example['objectAttribute']->store();

        self::assertTrue($example['objectAttribute']->attribute('has_content'));
        self::assertEquals($example['objectAttribute']->attribute('content'), $testContent);
    }

    public function testIsInformationCollector()
    {
        $example = eZSelection2TestSuite::createSelection();

        self::assertTrue($example['datatype']->isInformationCollector());
    }


    public function testGenerateIdentifier()
    {
        $example = eZSelection2TestSuite::createSelection();

        // Test for no input
        self::assertEquals($example['datatype']->generateIdentifier(null, null), "");

        self::assertEquals($example['datatype']->generateIdentifier("TestIdent", array()), "testident");
        self::assertEquals($example['datatype']->generateIdentifier("TestIdent_1", array()), "testident_1");
        self::assertEquals($example['datatype']->generateIdentifier("TestIdent", array("testident")), "testident__1");
        self::assertEquals($example['datatype']->generateIdentifier("TestIdent", array("testident","testident__1")), "testident__2");
    }

    public function testFetchCollectionAttributeHTTPInput()
    {
        $example = eZSelection2TestSuite::createSelection();

        // Load content into class attribute
        $exampleXML = eZSelection2TestSuite::fetchXMLSelection();
        $newContent = $example['datatype']->xmlToClassContent( $exampleXML );
        $example['classAttribute']->setContent( $newContent );
        $example['classAttribute']->store();
        $example['classAttribute']->storeDefined();

        // Setup the POST variable
        $http = eZHTTPTool::instance();
        $objectAttributeID = $example['objectAttribute']->attribute( 'id' );
        $http->setPostVariable("ContentObjectAttribute_ezselection2_selection_".$objectAttributeID, array("option1","option2"));

        // Create a fake collection
        $collection = eZInformationCollection::create( $example['object']->attribute('id'), eZInformationCollection::currentUserIdentifier() );
        $collectionAttribute = eZInformationCollectionAttribute::create( $collection->attribute( 'id' ) );

        // Fetch information
        $example['datatype']->fetchCollectionAttributeHTTPInput(  $collection, 
                                                                  $collectionAttribute, 
                                                                  $http, 
                                                                  "ContentObjectAttribute",  
                                                                  $example['objectAttribute']);

        self::assertEquals( $collectionAttribute->attribute('data_text'), "Option1,Option2");
    }

    public function testMetaData()
    {
        $example = eZSelection2TestSuite::createSelection();

        // Load content into class attribute
        $exampleXML = eZSelection2TestSuite::fetchXMLSelection();
        $newContent = $example['datatype']->xmlToClassContent( $exampleXML );
        $example['classAttribute']->setContent( $newContent );
        $example['classAttribute']->store();
        $example['classAttribute']->storeDefined();

        $example['objectAttribute']->setContent( array ("option1","option2","option3" ));
        $example['objectAttribute']->store();

        self::assertEquals( $example['datatype']->metaData( $example['objectAttribute']), 
                          "Option1 Option2 Option3 " );

    }

    public function testTitle()
    {
        $example = eZSelection2TestSuite::createSelection();

        // Load content into class attribute
        $exampleXML = eZSelection2TestSuite::fetchXMLSelection();
        $newContent = $example['datatype']->xmlToClassContent( $exampleXML );
        $example['classAttribute']->setContent( $newContent );
        $example['classAttribute']->store();
        $example['classAttribute']->storeDefined();

        $example['objectAttribute']->setContent( array ("option1","option2","option3" ));
        $example['objectAttribute']->store();

        self::assertEquals( $example['datatype']->title( $example['objectAttribute']),
                          "Option1,Option2,Option3" );

    }

    public function testIsIndexable()
    { 
        $example = eZSelection2TestSuite::createSelection();

        self::assertTrue($example['datatype']->isIndexable());
    }

    public function testSortKey()
    {
        $example = eZSelection2TestSuite::createSelection();

        // Load content into object attribute
        $example['objectAttribute']->setContent( array ("option1","option2","option3","option4" ));
        $example['objectAttribute']->store();

        self::assertEquals($example['datatype']->sortKey( $example['objectAttribute'] ), "option1 option2 option3 option4");
    }

    public function testSortKeyType()
    {
        $example = eZSelection2TestSuite::createSelection();

        self::assertEquals($example['datatype']->sortKeyType(), "string");
    }

    public function testSerializeContentClassAttribute()
    {
        $example = eZSelection2TestSuite::createSelection();

        $dom1 = new DOMDocument( '1.0', 'utf-8' );
        $node1 = $dom1->createElement( 'test' );
        $dom1->appendChild($node1);

        // Serialise the content into our DOM and return content
        $example['datatype']->serializeContentClassAttribute( $example['classAttribute'], null, $node1 );
        $content = $example['datatype']->xmlToClassContent( $dom1->saveXML() );

        // Confirm the content is empty        
        self::assertTrue(empty($content['delimiter']));
        self::assertTrue(empty($content['is_multiselect']));
        self::assertTrue(empty($content['query']));
        self::assertTrue(empty($content['is_checkbox']));
        self::assertTrue(empty($content['options']));

        // Try another dom with different content
        $dom2 = new DOMDocument( '1.0', 'utf-8' );
        $node2 = $dom2->createElement( 'test' );
        $dom2->appendChild($node2);
        $exampleXML = eZSelection2TestSuite::fetchXMLSelection();
        $example['classAttribute']->setContent( $example['datatype']->xmlToClassContent( $exampleXML ) );
        $example['classAttribute']->storeDefined();

        $example['datatype']->serializeContentClassAttribute( $example['classAttribute'], null, $node2 );
        $content = $example['datatype']->xmlToClassContent( $dom2->saveXML() );

        self::assertTrue(empty($content['delimiter']));
        self::assertEquals($content['is_multiselect'], 1);
        self::assertTrue(empty($content['query']));
        self::assertEquals($content['is_checkbox'], 1);

        // Assert that all options are correct
        foreach( $content['options'] as $index => $option )
        {
            self::assertEquals($option['name'], "Option".($index+1));
            self::assertEquals($option['identifier'], "option".($index+1));
        }

    }

    public function testUnserializeContentClassAttribute()
    {
        $example = eZSelection2TestSuite::createSelection();
        $exampleXML = eZSelection2TestSuite::fetchXMLSelection();

        $exampleContent = $example['datatype']->xmlToClassContent( $exampleXML );

        $doc = new DOMDocument( '1.0', 'utf-8' );
        $node = $doc->createElement( 'test' );
        $doc->appendChild($node);

        // Creata a dom from the example XML
        $example['datatype']->createDOMFromContent($exampleContent, $doc, $node);

        // Unserialise the content from our DOM. The result will be set in the class attribute
        $example['datatype']->unserializeContentClassAttribute( $example['classAttribute'], null, $node );

        // Test that the class attribute content is set correctly
        $content = $example['classAttribute']->attribute('content');
        self::assertTrue(empty($content['delimiter']));
        self::assertEquals($content['is_multiselect'], 1);
        self::assertTrue(empty($content['query']));
        self::assertEquals($content['is_checkbox'], 1);

        // Assert that all options are correct
        foreach( $content['options'] as $index => $option )
        {
            self::assertEquals($option['name'],"Option".($index+1));
            self::assertEquals($option['identifier'],"option".($index+1));
        }
    }
    
    public function testSerializeContentObjectAttribute()
    {
    }

    public function testUnserializeContentObjectAttribute()
    {
    }

    public function testToString()
    {
    }

    public function testFromString()
    {
    }

    public function testValidateClassAttributeHTTPInput()
    {
        self::markTestSkipped( "Not required for this datatype." );
    }

    public function testStoreClassAttribute()
    {
        self::markTestSkipped( "Tested via other methods." );
    }

    public function testDatatypeContructor()
    {
        self::markTestSkipped( "Tested via other methods." );
    }

    public function testCustomObjectAttributeHTTPAction()
    {
        self::markTestSkipped( "Not required for this datatype." );
    }

    public function testCustomClassAttributeHTTPAction()
    {
        self::markTestSkipped( "Not required for this datatype." );
    }

    public function testValidateObjectAttributeHTTPInput()
    {
        self::markTestSkipped( "Tested via testValidateCollectionAttributeHTTPInput." );
    }

    public function testValidateAttributeHTTPInput()
    {
        self::markTestSkipped( "Tested via testValidateCollectionAttributeHTTPInput." );
    }

    public function testObjectAttributeContent()
    {
        self::markTestSkipped( "Tested via other methods." );
    }

    public function testHasObjectAttributeContent()
    {
        self::markTestSkipped( "Tested via other methods." );
    }

    public function testStoreObjectAttribute()
    {
        self::markTestSkipped( "Tested via other methods." );
    }
}


?>