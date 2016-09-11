<?php

namespace Marc\Fields;

use Marc\Field;

class DataField implements Field
{
    /**
     * @var string
     */
    private $tag;

    /**
     * @var array
     */
    private $indicators = [null, null];

    /**
     * @var array
     */
    private $subFields = [];

    /**
     * @param string $tag
     * @param array $indicators
     */
    public function __construct($tag, array $indicators = [])
    {
        $this->setTag($tag);
    }

    /**
     * @inheritdoc
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return array
     */
    public function getIndicators()
    {
        return $this->indicators;
    }

    /**
     * @param array $indicators
     * @throws \InvalidArgumentException
     */
    public function setIndicators(array $indicators)
    {
        if (count($indicators) > 2) {
            throw new \InvalidArgumentException('Only allow 2 indicators');
        }
        $this->indicators = $indicators;
    }

    /**
     * @param string $indicator
     */
    public function setIndicator1($indicator)
    {
        $this->indicators[0] = $indicator;
    }

    /**
     * @param string $indicator
     */
    public function setIndicator2($indicator)
    {
        $this->indicators[1] = $indicator;
    }

    /**
     * @return string
     */
    public function getIndicator1()
    {
        return $this->indicators[0];
    }

    /**
     * @return string
     */
    public function getIndicator2()
    {
        return $this->indicators[1];
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException('Attribute ' . $name . ' does not exists');
        }

        return call_user_func([$this, $method]);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $method = 'set' . ucfirst($name);
        if (!method_exists($this, $method)) {
            throw new \InvalidArgumentException('Attribute ' . $name . ' does not exists');
        }

        call_user_func([$this, $method], $value);
    }

    public function addSubField(SubField $subField)
    {
        if (!in_array($subField, $this->subFields))
            $this->subFields[] = $subField;
    }

}