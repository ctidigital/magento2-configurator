<?php

namespace CtiDigital\Configurator\Model\Component;

abstract class ComponentAbstractTestCase extends \PHPUnit_Framework_TestCase
{

    protected $component;

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
        $this->assertClassHasAttribute('alias', $this->component);
    }

    public function testItHasAName()
    {
        $this->assertClassHasAttribute('name', $this->component);
    }
}