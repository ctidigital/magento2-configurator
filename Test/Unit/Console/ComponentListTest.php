<?php

namespace CtiDigital\Configurator\Model;

class ComponentListTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var ComponentList
     */
    private $componentList;

    /**
     * @var ComponentListInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $componentListInterface;

    protected function setUp()
    {
        $this->componentListInterface = $this->getMock(ComponentListInterface::class);

        // @todo use data (or similar) from di.xml preferably
        $this->componentListInterface->method('getComponents')->willReturn(array('websites'));

        $this->componentList = new ComponentList($this->componentListInterface->getComponents());
    }

    public function testComponentListIsArray()
    {
        $this->assertTrue(is_array($this->componentList->getComponents()));
    }
}
