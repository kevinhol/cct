<?php

require_once '../bootstrap.php';

/**
 * assertTrue() test case.
 */
class assertTrueTest extends PHPUnit_Framework_TestCase
{

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        
        // TODO Auto-generated assertTrueTest::setUp()
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated assertTrueTest::tearDown()
        parent::tearDown();
    }

    /**
     * Constructs the test case.
     */
    public function __construct()
    {
        // TODO Auto-generated constructor
    }

    /**
     * Tests assertTrue()
     */
    public function testAssertTrue()
    {

        // Data expected in Login POST array
        $loginPostArray = array(
            'app-id' => 'app_20424499_1510894862892',
            'environment' => 'https://apps.collabservintegration.com'
        );
        
        $site_configs = parse_ini_file("../../configs/main.ini");
        
        // Verify the application id passed against the regex check in the function
        $this->assertTrue(verifyAppID($site_configs, $loginPostArray));
        
        // verify the selected environment is valid
        $this->assertTrue(verifyEnvSelection($site_configs, $loginPostArray));
        
    }
}

