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

    public function testCreateCachePath()
    {
        $result = Test_Util::invokeStaticMethod('RakutenTag', 'createCachePath', array('a/b'));
        $this->assertEquals('a_slash_b.txt', basename($result));
    }
}

