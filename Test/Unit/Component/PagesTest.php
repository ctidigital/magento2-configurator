<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Pages;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use CtiDigital\Configurator\Helper\Component;

class PagesTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $pageRepository = $this->getMock(PageRepositoryInterface::class);
        $pageFactory = $this->getMock(PageInterfaceFactory::class);
        $componentHelper = $this->getMock(Component::class, [], [], '', false);

        $this->component =
            new Pages(
                $this->logInterface,
                $this->objectManager,
                $pageRepository,
                $pageFactory,
                $componentHelper
            );

        $this->className = Pages::class;
    }
}
