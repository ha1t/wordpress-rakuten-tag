<?php
/**
 *
 *
 */
class RakutenTagTest extends PHPUnit_Framework_TestCase
{
    public function testCreateInstance()
    {
        $RakutenTag = new RakutenTag();
        $this->assertEquals('rakutentag', strtolower(get_class($RakutenTag)));
    }
}

