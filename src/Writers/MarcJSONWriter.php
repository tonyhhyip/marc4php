<?php

namespace Marc\Writers;

use Marc\Fields\ControlField;
use Marc\Fields\DataField;
use Marc\Fields\SubField;
use Marc\Record;

class MarcJSONWriter extends AbstractWriter
{
    /**
     * @inheritdoc
     */
    public function toString()
    {
        $data = array_map(function (Record $record) {
            return $this->processArray($record->toArray());
        }, $this->collection->getRecords());
        return json_encode($data);
    }

    /**
     * @param array $record
     *
     * @return array
     */
    private function processArray(array $record)
    {
        $record['datafield'] = $this->processDataFields($record['datafield']);
        $record['controlfield'] = $this->processControlFields($record['controlfield']);
        return $record;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    private function processDataFields(array $fields)
    {
        return array_map(function (DataField $field) {
            return [
                'tag' => $field->getTag(),
                'ind' => implode($field->getIndicators()),
                'subfield' => $this->processSubField($field->getSubFields())
            ];
        }, $fields);
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    private function processControlFields(array $fields)
    {
        return array_map(function (ControlField $field) {
            return ['tag' => $field->getTag(), 'data' => $field->getContent()];
        }, $fields);
    }

    private function processSubField(array $fields)
    {
        $tmp = array_map(function (SubField $field) {
            return ['code' => $field->getCode(), 'data' => $field->getContent()];
        }, $fields);

        usort($tmp, function ($a, $b) {
            if ($a['code'] === $b['code']) return 0;
            return ((string)$a['code']) > ((string)$b['code']) ? 1 : -1 ;
        });

        return $tmp;
    }
}