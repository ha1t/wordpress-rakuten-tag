<?php
/**
 *
 *
 */
class RakutenTagTest extends TestCase
{
    public function testCreateInstance()
    {
        $RakutenTag = new RakutenTag();
        $this->assertEquals('rakutentag', strtolower(get_class($RakutenTag)));
    }

    public function testCreateCachePath()
    {
        $result = Test_Util::invokeStaticMethod('RakutenTag', 'createCachePath', array('a/b'));
        $this->assertFalse(strpos(basename($result), '/'));

        $result = Test_Util::invokeStaticMethod('RakutenTag', 'createCachePath', array('a b'));
        $this->assertFalse(strpos(basename($result), ' '));
    }
}

