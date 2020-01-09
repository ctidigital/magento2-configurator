<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Pages;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreRepository;

class PagesTest extends ComponentAbstractTestCase
{
    protected function componentSetUp()
    {
        /** @var PageRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject $pageRepository */
        $pageRepository = $this->getMockBuilder(PageRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var PageInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject $pageFactory */
        $pageFactory = $this->getMockBuilder(PageInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var StoreRepository|\PHPUnit_Framework_MockObject_MockObject $storeRepository */
        $storeRepository = $this->getMockBuilder(StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component =
            new Pages(
                $this->logInterface,
                $this->objectManager,
                $this->json,
                $pageRepository,
                $pageFactory,
                $storeRepository
            );

        $this->className = Pages::class;
    }
}
