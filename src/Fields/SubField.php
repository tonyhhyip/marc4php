<?php

namespace Marc\Fields;

class SubField
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $content;

    /**
     *
     * @param string $code
     * @param string $content
     */
    public function __construct($code, $content = '')
    {
        $this->setCode($code);
        $this->setContent($content);
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
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