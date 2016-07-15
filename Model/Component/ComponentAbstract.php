<?php

namespace CtiDigital\Configurator\Model\Component;

abstract class ComponentAbstract
{

    protected $alias;
    protected $name;

    abstract protected function parse($file = null);

    abstract protected function process($data = null);
}