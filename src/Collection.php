<?php

namespace Marc;


class Collection
{
    /**
     * @var array
     */
    private $records = [];

    /**
     * Collection constructor.
     *
     * @param array $records
     */
    public function __construct(array $records = [])
    {
        $this->addRecords($records);
    }

    /**
     * @param array $records
     */
    public function addRecords(array $records)
    {
        array_walk($records, function(Record $record, $_) {
            $this->addRecord($record);
        });
    }

    public function addRecord(Record $record)
    {
        $this->records[] = $record;
    }

    public function getRecords()
    {
        return $this->records;
    }
}