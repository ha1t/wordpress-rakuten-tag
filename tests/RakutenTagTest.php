<?php
/**
 *
 *
 */
final class RakutenTagTest extends TestCase
{
    public function testCreateInstance()
    {
        $RakutenTag = new RakutenTag();
        $this->assertEquals('rakutentag', strtolower(get_class($RakutenTag)));
    }
}

