<?php

namespace Marc;

interface Field
{
    /**
     * @return string
     */
    public function getTag();

    /**
     * @param string $tag
     *
     * @return void
     */
    public function setTag($tag);
}