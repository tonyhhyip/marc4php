<?php

namespace Marc;

interface Reader
{
    /**
     * @param $data
     *
     * @return Collection
     */
    public function parse($data);

    /**
     * @param string $file
     *
     * @return Collection
     */
    public function loadFile($file);
}