<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Rewrites;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Model\UrlRewriteFactory;

class RewritesTest extends ComponentAbstractTestCase
{
    protected function componentSetUp()
    {
        $urlPersistInterface = $this->getMockBuilder(UrlPersistInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlRewriteFactory = $this->getMockBuilder(UrlRewriteFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = new Rewrites(
            $this->logInterface,
            $this->objectManager,
            $urlPersistInterface,
            $urlRewriteFactory
        );

        $this->className = Rewrites::class;
    }
}
