<?php

namespace Marc\Readers;

use Marc\Reader;

abstract class AbstractReader implements Reader
{
    /**
     * @inheritdoc
     */
    public function loadFile($file)
    {
        return $this->parse(file_get_contents($file));
    }
}