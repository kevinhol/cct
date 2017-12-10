<?php

require_once '../bootstrap.php';


/**
 * assertEquals() test case.
 */
final class assertEqualsTest extends PHPUnit_Framework_TestCase
{

    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        
        // TODO Auto-generated assertEqualsTest::setUp()
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated assertEqualsTest::tearDown()
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
     * Tests assertEquals()
     */
    public function testAssertEquals()
    {
        // TODO Auto-generated assertEqualsTest->testAssertEquals
        //$this->markTestIncomplete("assertEquals() test not implemented");
        
        $site_configs = parse_ini_file("../../configs/main.ini");
        //var_dump($site_configs);
        
        // Data expected in Login POST array
        $loginPostArray = array(
            'app-id' => 'app_20424499_1510894862892',
            'environment' => 'https://apps.collabservintegration.com'
        );
        
        $this->assertEquals($loginPostArray['environment'], $site_configs['cloudEnvs'][0]);
        $this->assertEquals(4, count($site_configs['cloudEnvs']));
        
        //test website url is read correctly
        $this->assertEquals("https://cct.mybluemix.net", $site_configs['website_www']);
        
        // test only pages with forms is correct 
        $this->assertEquals(5, count($site_configs['pages_with_forms']));
        
        // test input sanitization (user imput XSS attack)
        $actual = '<script/>TEST TEST<script>';
        $expected = 'TEST TEST';
        $this->assertEquals($expected, sanitize_input($actual));
        
        $actual = '<script/>TEST TEST <script>alert();<script>';
        $expected = 'TEST TEST alert();';

        $this->assertEquals($expected, sanitize_input($actual));

        $actual = '<script/>TEST TEST <script>alert()  ; PRESERVE WHITE SPACE <script>';
        $expected = 'TEST TEST alert()  ; PRESERVE WHITE SPACE ';
        
        $this->assertEquals($expected, sanitize_input($actual));
    }
    
    
}

