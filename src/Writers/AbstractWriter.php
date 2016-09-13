<?php

namespace Marc\Writers;

use Marc\Collection;
use Marc\Writer;

abstract class AbstractWriter implements Writer
{

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @inheritdoc
     */
    public function setCollections(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @inheritdoc
     */
    public function outputToFile($file)
    {
        file_put_contents($file, $this->toString());
    }

    public function __toString()
    {
        return $this->toString();
    }
}