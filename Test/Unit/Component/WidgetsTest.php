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
        $storeFactory = $this->getMock(StoreFactory::class);
        $widgetCollection = $this->getMock(WidgetCollection::class, [], [], '', false);
        $themeCollection = $this->getMock(ThemeCollection::class, [], [], '', false);

        $this->component = new Widgets(
            $this->logInterface,
            $this->objectManager,
            $widgetCollection,
            $storeFactory,
            $themeCollection
        );
        $this->className = Widgets::class;
    }
}
