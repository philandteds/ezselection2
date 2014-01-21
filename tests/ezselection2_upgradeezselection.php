<?php

class eZSelection2UpgradeeZSelection extends ezpTestCase
{
    public function __construct()
    {
        parent::__construct();
        $this->setName( "eZSelection2 Upgrade eZ Selection Tests" );
    }

    /**
     * Force DB per test
     */
    protected function setUp()
    {
        parent::setUp();

        eZSelection2TestSuite::setUpDatabase();
    }

    /*
     * 
     */
    public function testUpgradeeZSelection()
    {
        $example = $this->createExampleClassAndObjects1();


        
    }

    public static function createExampleClassAndObjects1()
    {
        // Login as superuser
        eZContentCreationFunctions::loginUser("admin");

        $identifier = "test_1";
        
        // Create test class
        $class = new ezpClass();

        $class->name = "Test 1";
        $class->classgroup = "Content";
        $class->objectnamepattern = "<title>";

        $class->add('ezstring','title');
        $class->add('ezselection','ezSelection1');
        $class->add('ezselection','ezSelection2');

        // Create two selections, one without multiselect.        
        $class->ezselection1->values = array( "data_int1" => 0,
                                             "data_text5" => '<?xml version="1.0" encoding="utf-8"?><ezselection><options><option id="0" name="Test Option"/><option id="1" name="Test Option2"/></options></ezselection>');

        // And another with multiselect
        $class->ezselection2->values = array( "data_int1" => 1,
                                             "data_text5" => '<?xml version="1.0" encoding="utf-8"?><ezselection><options><option id="0" name="Test Option3"/><option id="1" name="Test Option4"/></options></ezselection>');

        $class->create();

        $datamap = $testObject->attribute('data_map');
        $objectAttribute = $datamap['ezselection1'];
        $classAttribute = $objectAttribute->attribute("contentclass_attribute");
        $datatype = $classAttribute->attribute('data_type');

        $object = eZSelection2UpgradeeZSelection::createObject($identifier, 2);
    }

    public static function createObject($class, $parent_node_id)
    {
        $testObject = new ezpObject("test_1", 2);
        $testObject->title = "Test 1";
        $testObject->publish();

        return array ( 'object' => $testObject,
                       'objectAttribute' => $objectAttribute,
                       'classAttribute' => $classAttribute,
                       'datatype' => $datatype );
    }
}

?>
