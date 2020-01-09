<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Widgets;
use Magento\Store\Model\StoreFactory;
use Magento\Widget\Model\ResourceModel\Widget\Instance\Collection as WidgetCollection;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;

class WidgetsTest extends ComponentAbstractTestCase
{
    protected function componentSetUp()
    {
        $storeFactory = $this->getMockBuilder(StoreFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $widgetCollection = $this->getMockBuilder(WidgetCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $themeCollection = $this->getMockBuilder(ThemeCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = new Widgets(
            $this->logInterface,
            $this->objectManager,
            $this->json,
            $widgetCollection,
            $storeFactory,
            $themeCollection
        );
        $this->className = Widgets::class;
    }
}
