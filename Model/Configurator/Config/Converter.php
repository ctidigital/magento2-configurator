<?php

namespace CtiDigital\Configurator\Model\Configurator\Config;

use Magento\Framework\Config\ConverterInterface;

class Converter implements ConverterInterface
{

    public function convert($source)
    {
        $output = [];

        //To get specific part of the XML config
        $xpath = new \DOMXPath($source);
        $components = $xpath->evaluate('/config/component');
        /** @var $typeNode \DOMNode */
        foreach ($components as $component) {
            $name = $component->getAttribute('name');
            $class = $component->getAttribute('class');
            $output['components'][$name] = array('name' => $name, 'class' => $class);
        }

        return $output;
    }
}
