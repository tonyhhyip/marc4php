<?php

namespace Marc\Writers;

use File_MARC;
use Marc\Fields\ControlField;
use Marc\Fields\DataField;
use Marc\Fields\SubField;
use Marc\Record;

class MarcMrcWriter extends AbstractWriter
{
    public function toString()
    {
        return implode(array_map(function (Record $record) {
            return $this->convertRecord($record);
        }, $this->collection->getRecords()));
    }

    private function convertRecord(Record $record)
    {
        $records = $record->toArray();
        $offset = 0;
        $fields = array_merge($records['subfield'], $records['datafield']);
        $content = [];
        $directory = [];

        foreach ($fields as $field) {
            $result = $field instanceof ControlField ?
                $this->convertControlField($field) : $this->convertDataField($field);
            $content[] = $result;

            $length = strlen($result);
            $entry = sprintf("%03s%04d%05d", $field->getTag(), $length, $offset);
            $directory[] = $entry;
            $offset += $length;
        }

        $base = File_MARC::LEADER_LEN + count($directory) * File_MARC::DIRECTORY_ENTRY_LEN + 1;
        $total = $base + $offset + 1;

        $record->setLeaderLength($total, $base);

        return $record->getLeader() . implode($directory) . File_MARC::END_OF_FIELD . implode($content) . File_MARC::END_OF_RECORD;
    }

    /**
     * @param ControlField $field
     *
     * @return string
     */
    private function convertControlField(ControlField $field)
    {
        return (string) $field->getContent() . File_MARC::END_OF_FIELD;
    }

    /**
     * @param DataField $field
     *
     * @return string
     */
    private function convertDataField(DataField $field)
    {
        $subFields = array_map(function (SubField $subField) {
            return $this->convertSubField($subField);
        }, $field->getSubFields());
        return (string)implode($field->getIndicators()) . implode($subFields) . File_MARC::END_OF_FIELD;
    }

    /**
     * @param SubField $subField
     *
     * @return string
     */
    private function convertSubField(SubField $subField)
    {
        $result = File_MARC::SUBFIELD_INDICATOR . $subField->getCode() . $subField->getContent();
        return (string) $result;
    }
}