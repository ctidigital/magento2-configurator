<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Widgets;
use Magento\Backend\Block\Widget\Grid\Column\Filter\Theme;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Widget\Model\ResourceModel\Widget\Instance\Collection as WidgetCollection;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;
use Psr\Log\LoggerInterface;

class WidgetsTest extends ComponentAbstractTestCase
{
    /**
     * Test 'where' condition for assertion
     */
    const TEST_WHERE_CONDITION = 'condition = 1';

    protected function componentSetUp()
    {
        $entityFactory = $this->getMock(EntityFactoryInterface::class);
        $logger = $this->getMock(LoggerInterface::class);
        $fetchStrategy = $this->getMock(FetchStrategyInterface::class);
        $eventManager = $this->getMock(ManagerInterface::class);

        $widgetSelect = $this->getMock('Magento\Framework\DB\Select', [], [], '', false);
        $themeSelect = $this->getMock('Magento\Framework\DB\Select', [], [], '', false);
        $widgetSelect->expects($this->any())->method('where')->with(self::TEST_WHERE_CONDITION);
        $themeSelect->expects($this->any())->method('where')->with(self::TEST_WHERE_CONDITION);
        $widgetResource = $this->getResource($widgetSelect);
        $themeResource = $this->getResource($themeSelect);

        $widgetArguments = array($entityFactory, $logger, $fetchStrategy, $eventManager, null, $widgetResource);
        $themeArguments = array($entityFactory, $logger, $fetchStrategy, $eventManager, null, $themeResource);
        $methods = array('getConnection');

        $storeFactory = $this->getMock(StoreFactory::class);
        $widgetCollection = $this->getMockBuilder(WidgetCollection::class)
            ->setConstructorArgs($widgetArguments)
            ->setMethods($methods)
            ->getMock();

        $themeCollection = $this->getMockBuilder(ThemeCollection::class)
            ->setConstructorArgs($themeArguments)
            ->setMethods($methods)
            ->getMock();

        $this->component = new Widgets(
            $this->logInterface,
            $this->objectManager,
            $widgetCollection,
            $storeFactory,
            $themeCollection
        );
        $this->className = Widgets::class;
    }

    /**
     * Retrieve resource model instance
     *
     * @param \Magento\Framework\DB\Select $select
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getResource(\Magento\Framework\DB\Select $select)
    {
        $connection = $this->getMock('Magento\Framework\DB\Adapter\Pdo\Mysql', [], [], '', false);
        $connection->expects($this->once())->method('select')->will($this->returnValue($select));
        $connection->expects($this->any())->method('quoteIdentifier')->will($this->returnArgument(0));

        $resource = $this->getMockForAbstractClass(
            'Magento\Framework\Model\ResourceModel\Db\AbstractDb',
            [],
            '',
            false,
            true,
            true,
            ['getConnection', 'getMainTable', 'getTable', '__wakeup']
        );
        $resource->expects($this->any())->method('getConnection')->will($this->returnValue($connection));
        $resource->expects($this->any())->method('getTable')->will($this->returnArgument(0));

        return $resource;
    }
}
