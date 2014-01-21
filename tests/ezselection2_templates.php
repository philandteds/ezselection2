<?php

class eZSelection2Templates extends ezpTestCase
{
    const TEST_TEMPLATE="extension/ezselection2/tests/ezselection2_test.tpl";

    public function __construct()
    {
        parent::__construct();
        $this->setName( "eZSelection2 Template Tests" );        
    }
	
    protected function setUp()
    {    	
    	parent::setUp();

        eZSelection2TestSuite::setUpDatabase();
    }

    /*
     * Test the system setup for this datatype.
     */
    public function testTemplatesExists()
    { 


        // Simple existance check with load check

        // Class view and edit
        $this->checkTemplateExists("extension/ezselection2/design/standard/templates/class/datatype/view/ezselection2.tpl");
        $this->checkTemplateExists("extension/ezselection2/design/standard/templates/class/datatype/edit/ezselection2.tpl");

        // Content view and edit
        $this->checkTemplateExists("extension/ezselection2/design/standard/templates/content/datatype/view/ezselection2.tpl");
        $this->checkTemplateExists("extension/ezselection2/design/standard/templates/content/datatype/edit/ezselection2.tpl");

        // Collect view
        $this->checkTemplateExists("extension/ezselection2/design/standard/templates/content/datatype/collect/ezselection2.tpl");

        // PDF view
        $this->checkTemplateExists("extension/ezselection2/design/standard/templates/content/datatype/pdf/ezselection2.tpl");

        // Result
        $this->checkTemplateExists("extension/ezselection2/design/standard/templates/content/datatype/result/info/ezselection2.tpl");
    }

    public function testClassEditTemplate()
    {
        $example = eZSelection2TestSuite::createSelection();

        $tpl = templateInit();
        $tpl->setVariable('type',  "classedit");
        $tpl->setVariable('attribute',  $example['classAttribute']);
        $classAttributeID = $example['classAttribute']->attribute('id');

        $dom = new DomDocument;

        // Examine whether the template produced html and loads into the DOM.
        $html = $tpl->fetch( self::TEST_TEMPLATE  );
        self::assertTrue( $dom->loadHTML($html));

        // Test with no data stored. There are 8 references
        $scripts = $dom->getElementsByTagName("script");
        self::assertEquals( $scripts->length, 8 );

        // Test the id is set correctly on input fields
        $xpath = new DOMXpath($dom);
        $values = array( "multi", "checkbox", "delimiter", "options2");
        foreach ($values as $value)
        {
            self::assertEquals( $xpath->query("//input[@name='ContentClass_ezselection2_".$value."_".$classAttributeID."']")->length, 1 );
        }

        // Examine javascript jsarray and perform an initial test
        $javascript = $xpath->query("//script[@name='tablevalues']");
        $content = $javascript->item(0)->textContent;
        $content = preg_replace("/\s\s/", "", $content);
        preg_match("/var data = \[(.*)\];/", $content, $match);
        $jsarray = $match[1];
        self::assertTrue(empty($jsarray));
    }

    public function testClassViewTemplate()
    {
        $example = eZSelection2TestSuite::createSelection();

        $tpl = templateInit();
        $tpl->setVariable('type',  "classview");
        $tpl->setVariable('attribute',  $example['classAttribute']);

        $dom = new DomDocument;

        // Examine whether the template produced html and loads into the DOM.
        self::assertTrue( $dom->loadHTML($tpl->fetch( self::TEST_TEMPLATE  ) ) );

        // Test with no data stored.
        $tableRows = $dom->getElementsByTagName("tr");
        self::assertEquals( $tableRows->length, 1 );

        // Create xpath element
        $xpath = new DOMXpath($dom);
        $elements = $xpath->query("//label");

        // Examine labels. This is order sensitive.
        $labels = array( "Option list:", "Multiple choice:", "Checkbox style:", "Delimiter:", "Database query:" );
        foreach ( $elements as $index => $element)
        {
            self::assertEquals( $element->textContent, $labels[$index] );
 
        }

        // Now load sample data
        $exampleXML = eZSelection2TestSuite::fetchXMLSelection();
        $newContent = $example['datatype']->xmlToClassContent( $exampleXML );
        $example['classAttribute']->setContent( $newContent );
        $example['classAttribute']->store();
        $example['classAttribute']->storeDefined();

        // Reload the dom with no whitespace
        $html = $tpl->fetch( self::TEST_TEMPLATE  );
        $html = preg_replace("/>\s+</", "><", $html);
        $dom->loadHTML( $html ) ;

        // Test with data stored. There should be six rows
        $tableRows = $dom->getElementsByTagName("tr");
        self::assertEquals( $tableRows->length, 6 );

        // Examine html elements
        $xpath = new DOMXpath($dom);
        $elements = $xpath->query("//tr/td");
        $values = array( "1.", "Option1","option1","",
                         "2.", "Option2","option2","",
                         "3.", "Option3","option3","",
                         "4.", "Option4","option4","",
                         "5.", "Option5","option5","");
        foreach ($elements as $index => $element)  
        {
            self::assertEquals( $element->textContent, $values[$index] );
        }

        // Example the values for the other boxes
        $elements = $xpath->query("//div[@class='element']");
        $values = array( "Multiple choice:Yes", "Checkbox style:Yes", "Delimiter:''");
        foreach ($elements as $index => $element)  
        {
            self::assertEquals( $element->textContent, $values[$index] );
        }       
    }

    public function testContentCollectViewTemplates()
    {
        $example = eZSelection2TestSuite::createSelection();

        // Fetch the second selection attribute which is an information collector. The
        // normal collect view should load.
        $datamap = $example['object']->attribute('data_map');
        $objectAttribute = $datamap['ezselection2_infocollection'];
        $objectAttributeID = $objectAttribute->attribute('id');
        $classAttribute = $objectAttribute->attribute("contentclass_attribute");

        $tpl = templateInit();
        $tpl->setVariable('type',  "contentview");
        $tpl->setVariable('attribute',  $objectAttribute);

        // Examine whether the template produced html and loads into the DOM.
        $dom = new DomDocument;
        self::assertTrue( $dom->loadHTML($tpl->fetch( self::TEST_TEMPLATE  ) ) );

        // Examine returned templates used. Only interested in the second template
        $templateFetchList = $tpl->templateFetchList();
        self::assertTrue( eregi ("design/standard/templates/content/datatype/collect/ezselection2.tpl", $templateFetchList[1]) > 0 );

        // Now load sample data and reload
        $exampleXML = eZSelection2TestSuite::fetchXMLSelection();
        $newContent = $example['datatype']->xmlToClassContent( $exampleXML );
        $classAttribute->setContent( $newContent );
        $classAttribute->store();
        $classAttribute->storeDefined();
        $tpl = templateInit();
        $tpl->setVariable('type',  "contentview");
        $tpl->setVariable('attribute',  $objectAttribute);
        $dom->loadHTML($tpl->fetch( self::TEST_TEMPLATE  ) ) ;
 
        // Test that the values are correct in the collect template
        $xpath = new DOMXpath($dom);
        $query1 = $xpath->query("//input[@name='ContentObjectAttribute_ezselection2_".$objectAttributeID."[]']");
        self::assertEquals( $query1->length, 5);

        foreach ($newContent['options'] as $option)
        {
           $query2 = $xpath->query("//input[@value='".$option['name']."']");
           self::assertEquals( $query2->length, 1);
        }
         
        // Test again, this time with an information collection attribute
        $tpl = templateInit();
        $tpl->setVariable('type',  "contentview");
        $tpl->setVariable('attribute',  $example['objectAttribute']);
        $dom = new DomDocument;

        // Examine whether the template produced html and loads into the DOM.
        $dom = new DomDocument;
        self::assertTrue( $dom->loadHTML($tpl->fetch( self::TEST_TEMPLATE  ) ) );

        // Examine returned templates used. Only interested in the second template
        $templateFetchList = $tpl->templateFetchList();
        self::assertTrue( eregi ("design/standard/templates/content/datatype/view/ezselection2.tpl", $templateFetchList[1]) > 0 );
  
    }
    private function checkTemplateExists($templateFile)
    {
        $tpl = templateInit();

        $tpl->fetch( $templateFile );
        self::assertFileExists($templateFile);
        self::assertTrue(strlen(array_pop($tpl->IncludeText)) > 10);

        unset($tpl);
    }
}

?>