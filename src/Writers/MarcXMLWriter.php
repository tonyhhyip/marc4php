<?php

namespace Marc\Writers;


use Marc\Collection;
use Marc\Fields\ControlField;
use Marc\Fields\DataField;
use Marc\Fields\SubField;
use Marc\Record;
use Sabre\Xml\Service;
use Sabre\Xml\Writer;

class MarcXMLWriter extends AbstractWriter
{
    const SCHEMA = 'http://www.loc.gov/MARC21/slim';

    /**
     * @var Service
     */
    private $writer;

    /**
     * @param Service|null $writer
     */
    public function __construct(Service $writer = null)
    {
        $this->writer = $writer ?: new Service();
        $this->configWriter();
    }

    /**
     * @inheritdoc
     */
    public function toString()
    {
        return (
            '<?xml version="1.0" encoding="UTF-8"?>' .
            $this->writer->write(static::nameElement('collection'), $this->collection)
        );
    }

    private function configWriter()
    {
        $this->writer->namespaceMap[static::SCHEMA] = 'marc';
        $this->writer->classMap['Marc\Collection'] = function (Writer $writer, Collection $entry) {
            $name = static::nameElement('collection');
            $writer->startElement($name);
            $records = $entry->getRecords();
            $this->writeRecords($records, $writer);
            $writer->endElement();
        };
    }

    /**
     * @param array $records
     * @param Writer $writer
     */
    private function writeRecords(array $records, Writer $writer)
    {
        array_map(function (Record $record) use ($writer) {
            $writer->startElement(static::nameElement('record'));
            $this->writeLeader($record->getLeader(), $writer);
            $this->writeControlFields($record->getControlFields(), $writer);
            $this->writeDataFields($record->getDataFields(), $writer);
            $writer->endElement();
        }, $records);
    }

    /**
     * @param $leader
     * @param Writer $writer
     */
    private function writeLeader($leader, Writer $writer)
    {
        $writer->writeElement(static::nameElement('leader'), $leader);
    }

    /**
     * @param array $fields
     * @param Writer $writer
     */
    private function writeControlFields(array $fields, Writer $writer)
    {
        array_map(function (ControlField $field) use ($writer) {
            $writer->write([
                'name' => static::nameElement('controlfield'),
                'value' => $field->getContent(),
                'attributes' => [
                    'tag' => $field->getTag()
                ]
            ]);
        }, $fields);
    }

    private function writeDataFields(array $fields, Writer $writer)
    {
        array_map(function (DataField $field) use ($writer) {
            $writer->startElement(static::nameElement('datafield'));
            $writer->writeAttributes([
                'tag' => $field->getTag(),
                'ind1' => $field->getIndicator1(),
                'ind2' => $field->getIndicator2()
            ]);
            $this->writeSubFields($field->getSubFields(), $writer);
            $writer->endElement();
        } , $fields);
    }

    /**
     * @param array $fields
     * @param Writer $writer
     */
    private function writeSubFields(array $fields, Writer $writer)
    {
        array_map(function (SubField $field) use ($writer) {
            $writer->write([
                'name' => static::nameElement('subfield'),
                'value' => $field->getContent(),
                'attributes' => [
                    'code' => $field->getCode()
                ]
            ]);
        }, $fields);
    }

    /**
     * @param string $element
     *
     * @return string
     */
    private static function nameElement($element)
    {
        return '{' . static::SCHEMA . '}' . $element;
    }
}