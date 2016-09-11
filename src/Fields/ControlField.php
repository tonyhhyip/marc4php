<?php

namespace Marc\Fields;

use Marc\Field;

class ControlField implements Field
{
    private $tag;

    /**
     * ControlField constructor.
     *
     * @param string $tag
     * @param string $content
     */
    public function __construct($tag, $content)
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
}