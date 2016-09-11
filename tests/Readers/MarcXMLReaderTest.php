<?php

namespace Marc\Tests\Readers;

use Marc\Readers\MarcXMLReader;

class MarcXMLReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $reader = new MarcXMLReader();
        $this->assertAttributeInstanceOf('Sabre\Xml\Service', 'reader', $reader);
        $result = $reader->loadFile(__DIR__ . '/../samples/namespace.xml');
        $this->assertInstanceOf('Marc\Collection', $result);
        $records = $result->getRecords();
        $this->assertInternalType('array', $records);
        $record = $records[0];
        $this->assertInstanceOf('Marc\Record', $record);
    }

    public function testOneRecord()
    {
        $reader = new MarcXMLReader();
        $result = $reader->loadFile(__DIR__ . '/../samples/onerecord.xml');
        $this->assertInstanceOf('Marc\Collection', $result);
    }
}
