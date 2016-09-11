<?php

namespace Marc;

interface Writer
{
    /**
     * @param Collection $collection
     *
     * @return void
     */
    public function setCollections(Collection $collection);

    /**
     * @param string $file
     */
    public function outputToFile($file);

    /**
     * @return string
     */
    public function toString();
}