<?php

require_once('kernel/common/template.php');

class eZSelection2TestSuite extends ezpDatabaseTestSuite
{
    public function __construct()
    {
    	parent::__construct();

    	// Appears to be essential for PHPUnit and eZ Publish.
        $this->createGlobalsReference = TRUE;
        $this->backupGlobals = FALSE;
    	
        $this->setName( "eZSelection2 Test Suite" );

//        $this->addTestSuite( 'eZSelection2Test' );
//        $this->addTestSuite( 'eZSelection2Templates' );
        $this->addTestSuite( 'eZSelection2UpgradeeZSelection' );

    }

    /*
     * Accesses 'site.ini' and queries for database settings.
     * 
     * @return array $settings
     */
    public function readeZPublishDatabaseSettings()
    {
        require_once('lib/ezutils/classes/ezini.php');
        $ini = eZINI::instance( 'site.ini');

        $dsn = "";

        if ($ini)
        {
            $settings = array('type'   => $ini->variable('DatabaseSettings', 'DatabaseImplementation'),
                              'dbname' => $ini->variable('DatabaseSettings', 'Database'),
                              'host' =>   $ini->variable('DatabaseSettings', 'Server'),
                              'user'   => $ini->variable('DatabaseSettings', 'User'),
                              'charset'=> $ini->variable('DatabaseSettings', 'Charset'),
                              'pass'   => $ini->variable('DatabaseSettings', 'Password'));

            // Simple slice to remove 'ez' from type name... Rewrite...
            if ( preg_match( "/^ez/", $settings['type']))
            {
            	$settings['type'] = substr($settings['type'],2);
            }
            
            // Simpe workaround for now. Add 'test' to database line
            $dsn = $settings['type']."://".$settings['user'].":".$settings['pass']."@".$settings['host']."/".$settings['dbname']."_test";
            
        }
        
        return $dsn;
    } 
    
    protected function setUp()
    {   
        // Clear cache
        $this->clearCache();
        
    	// Declare database to use
//    	$this->setUpDatabase();
    	
    	// Setup log
    	new ezpLog("eztest.log");
    	
    	ezpLog::log( "Setup complete.", ezcLog::INFO ); 
    }    
    
    public function setUpDatabase()
    { 
        try
        {
            $dsn = ezpTestRunner::dsn();
        }
        catch ( ezcConsoleOptionMandatoryViolationException $e)
        {
            $dsn = new ezpDsn( eZSelection2TestSuite::readeZPublishDatabaseSettings()  );
        }

        $sharedFixture = ezpTestDatabaseHelper::create( $dsn );

        ezpTestDatabaseHelper::insertDefaultData( $sharedFixture );

//        if ( count( $this->sqlFiles > 0 ) )
//            ezpTestDatabaseHelper::insertSqlData( $sharedFixture, $this->sqlFiles );
        
         eZDB::setInstance( $sharedFixture );
    }

    private function clearCache()
    {
        if (is_file('bin/php/ezcache.php')) 
        {
            $status = 0;
            exec('php bin/php/ezcache.php --clear-all --purge', $dummy, $status);

            if ($status) 
            {
            	$log = ezcLog::getInstance();
                $log->log("Unable to clear cache.", ezcLog::INFO);
            }
        }

        /**
         *  Clear all eZ global settings
         */
        foreach(array_keys($GLOBALS) as $global) 
        {
            if (preg_match('/^eZContent/', $global ))
            {
                unset($GLOBALS[$global]);
            }
        }
    }
    
    public static function suite()
    {
        return new self();
    }

    public static function fetchXMLSelection()
    {
        return '<?xml version="1.0"?>
                <content>
                   <options>
                       <option name="Option1" identifier="option1" value=""/>
                       <option name="Option2" identifier="option2" value=""/>
                       <option name="Option3" identifier="option3" value=""/>
                       <option name="Option4" identifier="option4" value=""/>
                       <option name="Option5" identifier="option5" value=""/>
                   </options>
                   <multiselect>1</multiselect>
                   <checkbox>1</checkbox>
                   <delimiter><![CDATA[]]></delimiter>
                   <query><![CDATA[]]></query>
                </content>';
    }

    public static function createSelection($name = "Test Class", $identifier="test_class")
    {
        // Login as superuser
        eZContentCreationFunctions::loginUser("admin");

        // Create test class
        $class = new ezpClass();

        $class->name = $name;
        $class->classgroup = "Content";
        $class->objectnamepattern = "<title>";

        $class->add('ezstring','title');
        $class->add('ezselection2','ezSelection2_infocollection');
        $class->add('ezselection2','ezSelection2');

        // Set class attribute property individually
        $class->title->required = true;
        $class->title->searchable = true;

        // Set this attribute to be required and an informationcollector
        $class->ezselection2->required = true;
        $class->ezselection2->informationcollector = false;

        $class->ezselection2_infocollection->required = false;
        $class->ezselection2_infocollection->informationcollector = true;

        $testClass = $class->create();

        // Now create an object
        $testObject = new ezpObject($identifier, 2);
        $testObject->name = $name." Object";
        $testObject->publish();

        $datamap = $testObject->attribute('data_map');
        $objectAttribute = $datamap['ezselection2'];
        $classAttribute = $objectAttribute->attribute("contentclass_attribute"); 
        $datatype = $classAttribute->attribute('data_type');

        return array ( 'object' => $testObject, 
                       'objectAttribute' => $objectAttribute, 
                       'classAttribute' => $classAttribute,
                       'datatype' => $datatype );
    }
}

?>