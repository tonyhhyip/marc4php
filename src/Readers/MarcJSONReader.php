<?php

namespace Marc\Readers;

use Marc\Collection;
use Marc\Fields\ControlField;
use Marc\Fields\DataField;
use Marc\Fields\SubField;
use Marc\Reader;
use Marc\Record;

class MarcJSONReader implements Reader
{
    /**
     * @inheritdoc
     */
    public function loadFile($file)
    {
        $content = file_get_contents($file);
        return $this->parse($content);
    }

    public function parse($data)
    {
        $content = json_decode($data, true);
        if (isset($content['leader'])) {
            $content = [$content];
        }

        return $this->convertArrayToCollection($content);
    }

    /**
     * @param array $content
     *
     * @return Collection
     */
    private function convertArrayToCollection(array $content)
    {
        $records = array_map(function ($record) {
            return $this->convertArrayToRecord($record);
        }, $content);
        return new Collection($records);
    }

    /**
     * @param array $content
     *
     * @return Record
     */
    private function convertArrayToRecord(array $content)
    {
        $controls = array_map(function ($control) {
            return $this->convertArrayToControlField($control);
        }, $content['controlfield']);
        $fields = array_map(function ($field) {
            return $this->convertArrayToDataField($field);
        }, $content['datafield']);
        $record = new Record($content['leader'], $controls, $fields);
        return $record;
    }

    /**
     * @param array $content
     *
     * @return ControlField
     */
    private function convertArrayToControlField(array $content)
    {
        return new ControlField($content['tag'], $content['data']);
    }

    /**
     * @param array $content
     *
     * @return DataField
     */
    private function convertArrayToDataField(array $content)
    {
        $field = new DataField($content['tag'], str_split($content['ind']));
        foreach ($content['subfield'] as $subfield) {
            $field->addSubField(new SubField($subfield['code'], $subfield['data']));
        }
        return $field;
    }
}