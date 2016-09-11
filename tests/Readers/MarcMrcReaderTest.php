<?php

namespace Marc\Tests\Readers;

use Marc\Readers\MarcMrcReader;

class MarcMrcReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $reader = new MarcMrcReader();
        $collection = $reader->loadFile(__DIR__ . '/../samples/sandburg.mrc');
        $this->assertInstanceOf('Marc\Collection', $collection);
    }
}
