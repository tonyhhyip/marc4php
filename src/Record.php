<?php

namespace Marc;

use Marc\Fields\ControlField;
use Marc\Fields\DataField;

class Record
{
    /**
     * @var string
     */
    private $leader;

    /**
     * @var array
     */
    private $controls = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * Record constructor.
     *
     * @param string $leader
     * @param array $controls
     * @param array $data
     */
    public function __construct($leader = '', array $controls = [], array $data = [])
    {
        $this->setLeader($leader);
        $this->setControlFields($controls);
        $this->setDataFields($data);
    }

    /**
     * @return string
     */
    public function getLeader()
    {
        return $this->leader;
    }

    /**
     * @param string $leader
     */
    public function setLeader($leader)
    {
        $this->leader = $leader;
    }

    /**
     * @param array $control
     */
    public function setControlFields(array $control)
    {
        $this->controls = $control;
    }

    /**
     * @return array
     */
    public function getControlFields()
    {
        return $this->controls;
    }

    /**
     * @param string $field
     *
     * @return ControlField
     */
    public function getControlField($field)
    {
        return $this->controls[$field];
    }

    /**
     * @param ControlField $field
     */
    public function setControlField(ControlField $field)
    {
        $code = $field->getTag();
        $this->controls[$code] = $field;
    }

    /**
     * @param array $data
     */
    public function setDataFields(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getDataFields()
    {
        return $this->data;
    }

    /**
     * @param DataField $data
     */
    public function addDataField(DataField $data)
    {
        $this->data[] = $data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'leader' => $this->leader,
            'controlfield' => $this->controls,
            'datafield' => $this->data,
        ];
    }
}