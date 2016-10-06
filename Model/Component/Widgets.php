<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;

class Widgets extends YamlComponentAbstract
{

    protected $alias = 'widgets';
    protected $name = 'Widgets';
    protected $description = 'Component to manage CMS Widgets';

    protected function processData($data = null)
    {
        try {
            print_r($data);
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }
}
