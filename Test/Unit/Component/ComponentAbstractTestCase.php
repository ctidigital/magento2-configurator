<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\ComponentAbstract;

abstract class ComponentAbstractTestCase extends \PHPUnit_Framework_TestCase
{

    /* @var $component ComponentAbstract */
    protected $component;

    /* @var $className String */
    protected $className;

    abstract protected function componentSetUp();

    protected function setUp()
    {
        $this->componentSetUp();
    }

    public function testItExtendsAbstract()
    {
        $this->assertInstanceOf(ComponentAbstract::class, $this->component);
    }


    public function testItHasAnAlias()
    {
        $this->assertClassHasAttribute('alias', $this->className);
        $this->assertNotEmpty(
            $this->component->getComponentAlias(),
            sprintf('No alias specified in component %s', $this->className)
        );
    }

    public function testItHasAName()
    {
        $this->assertClassHasAttribute('name', $this->className);
        $this->assertNotEmpty(
            $this->component->getComponentName(),
            sprintf('No name specified in component %s', $this->className)
        );
    }
}
