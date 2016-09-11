<?php

namespace Marc\Readers;

use Scriptotek\Marc\Collection as MarcCollection;
use Scriptotek\Marc\Record as MarcRecord;
use Scriptotek\Marc\Records;
use Marc\Fields\ControlField;
use Marc\Fields\DataField;
use Marc\Fields\SubField;
use Marc\Record;
use Marc\Collection;
use Marc\Reader;

class MarcMrcReader implements Reader
{
    /**
     * @inheritdoc
     */
    public function parse($data)
    {
        $records = MarcCollection::fromString($data)->records;
        return $this->convertRecord($records);
    }

    /**
     * @inheritdoc
     */
    public function loadFile($file)
    {
        $collection = MarcCollection::fromFile($file);
        return $this->convertRecord($collection->records);
    }

    /**
     * @param Records $records
     *
     * @return Collection
     */
    private function convertRecord(Records $records)
    {
        $records = array_map(function (MarcRecord $item) {
            $record = new Record($item->getLeader());
            $fields = $item->getFields();
            foreach ($fields as $field) {
                if ($field instanceof \File_MARC_Control_Field) {
                    $record->setControlField($this->convertControlField($field));
                } elseif ($field instanceof \File_MARC_Data_Field) {
                    $record->addDataField($this->convertDataField($field));
                } else {
                    throw new \InvalidArgumentException('Unknown Field Type');
                }
            }
            return $record;
        }, iterator_to_array($records));

        $collection = new Collection($records);
        return $collection;
    }

    /**
     * @param \File_MARC_Control_Field $field
     *
     * @return ControlField
     */
    private function convertControlField(\File_MARC_Control_Field $field)
    {
        $tag = $field->getTag();
        $data = $field->getData();
        return new ControlField($tag, $data);
    }

    /**
     * @param \File_MARC_Data_Field $field
     *
     * @return DataField
     */
    private function convertDataField(\File_MARC_Data_Field $field)
    {
        $tag = $field->getTag();
        $ind1 = $field->getIndicator(1);
        $ind2 = $field->getIndicator(2);
        $dataField = new DataField($tag, [$ind1, $ind2]);
        foreach ($field->getSubfields() as $subField)
            $dataField->addSubField($this->convertSubfield($subField));
        return $dataField;
    }

    /**
     * @param \File_MARC_Subfield $field
     *
     * @return SubField
     */
    private function convertSubfield(\File_MARC_Subfield $field)
    {
        $code = $field->getCode();
        $data = $field->getData();
        return new SubField($code, $data);
    }
}