<?php

namespace Marc\Fields;

use Marc\Field;

class ControlField implements Field
{
    /**
     * @var string
     */
    private $tag;

    /**
     * @var string
     */
    private $content;

    /**
     * ControlField constructor.
     *
     * @param string $tag
     * @param string $content
     */
    public function __construct($tag, $content)
    {
        $this->setTag($tag);
        $this->setContent($content);
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
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}