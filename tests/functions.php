<?php

use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;

function dd()
{
    array_map(function ($x) {
        (new CliDumper)->dump((new VarCloner)->cloneVar($x));
    }, func_get_args());
    die(1);
}