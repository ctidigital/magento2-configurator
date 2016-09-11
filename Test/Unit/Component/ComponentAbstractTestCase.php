<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\ComponentAbstract;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Framework\ObjectManagerInterface;

abstract class ComponentAbstractTestCase extends \PHPUnit_Framework_TestCase
{

    /* @var $component ComponentAbstract */
    protected $component;

    /* @var $className String */
    protected $className;

    /* @var $logInterface LoggingInterface */
    protected $logInterface;

    /* @var $objectManager \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    abstract protected function componentSetUp();

    protected function setUp()
    {
        $this->objectManager = $this->getMock(ObjectManagerInterface::class);
        $this->logInterface = $this->getMock(LoggingInterface::class);
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
