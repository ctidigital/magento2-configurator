<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\ProductLinks;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

class ProductLinksTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $this->className = ProductLinks::class;
        $productLinkFactory = $this->getMockBuilder(ProductLinkInterfaceFactory::class)->getMock();
        $productRepository = $this->getMockBuilder(ProductRepositoryInterface::class)->getMock();
        $this->component = new ProductLinks(
            $this->logInterface,
            $this->objectManager,
            $this->json,
            $productRepository,
            $productLinkFactory
        );
    }
}
