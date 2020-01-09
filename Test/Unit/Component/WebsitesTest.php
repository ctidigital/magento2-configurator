<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Websites;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;

class WebsitesTest extends ComponentAbstractTestCase
{
    protected $websiteFactory;
    protected $storeFactory;
    protected $groupFactory;
    protected $indexerFactory;
    protected $eventManager;

    protected function componentSetUp()
    {
        $this->className = Websites::class;
        $this->websiteFactory = $this->getMockBuilder(WebsiteFactory::class)->setMethods(['create'])->getMock();
        $this->storeFactory = $this->getMockBuilder(StoreFactory::class)->setMethods(['create'])->getMock();
        $this->groupFactory = $this->getMockBuilder(GroupFactory::class)->setMethods(['create'])->getMock();
        $this->indexerFactory = $this->getMockBuilder('\Magento\Indexer\Model\IndexerFactory')
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventManager = $this->getMockBuilder('Magento\Framework\Event\ManagerInterface')->getMock();

        $this->component = new Websites(
            $this->logInterface,
            $this->objectManager,
            $this->json,
            $this->indexerFactory,
            $this->eventManager,
            $this->websiteFactory,
            $this->storeFactory,
            $this->groupFactory
        );
    }
}
