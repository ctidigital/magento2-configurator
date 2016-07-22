<?php

namespace CtiDigital\Configurator\Console;

use CtiDigital\Configurator\Model\ConfiguratorListInterface;

class ComponentListTest implements \PHPUnit_Framework_TestCase
{

    /**
     * @var ComponentList
     */
    private $componentList;

    /**
     * @var ConfiguratorListInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configuratorList;

    protected function setUp()
    {
        $this->configuratorList = $this->getMock(ConfiguratorListInterface::class);

        $this->componentList = new ComponentList($this->configuratorComponentList);
    }
}
