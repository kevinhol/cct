<?php

/**
 * assertRegExp() test case.
 */
class assertRegExpTest extends PHPUnit_Framework_TestCase
{

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        
        // TODO Auto-generated assertRegExpTest::setUp()
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated assertRegExpTest::tearDown()
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
     * Tests assertRegExp()
     */
    public function testAssertRegExp()
    {
        $site_configs = parse_ini_file("../../configs/main.ini");
        
        $this->assertRegExp($site_configs['commUuidRegex'], '4021cbfe-77ed-4a39-89de-59b2fd63adb5');
        
    }
}

