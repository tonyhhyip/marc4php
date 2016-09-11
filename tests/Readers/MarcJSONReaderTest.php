<?php

namespace Marc\Tests\Readers;

use Marc\Readers\MarcJSONReader;

class MarcJSONReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $reader = new MarcJSONReader();
        $result = $reader->loadFile(__DIR__ . '/../samples/sandburg.json');
        $this->assertInstanceOf('Marc\Collection', $result);
    }
}
