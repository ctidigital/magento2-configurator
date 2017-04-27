<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Products;
use Firegento\FastSimpleImport\Model\ImporterFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Http\ZendClient;
use Magento\Framework\Http\ZendClientFactory;
use Magento\Framework\App\Response\Http\FileFactory;

class ProductsTest extends ComponentAbstractTestCase
{
    protected function componentSetUp()
    {
        $importerFactoryMock = $this->getMock(ImporterFactory::class);
        $productFactoryMock = $this->getMock(ProductFactory::class);
        $httpClientMock = $this->getMock(
            ZendClientFactory::class,
            ['create'],
            [],
            '',
            false
        );
        $mockFileFactory = $this->getMock(
            FileFactory::class,
            ['create'],
            [],
            '',
            false
        );

        $this->component = $this->testObjectManager->getObject(
            Products::class,
            [
                'importerFactory' => $importerFactoryMock,
                'productFactory' => $productFactoryMock,
                'httpClientFactory' => $httpClientMock,
                'fileFactory' => $mockFileFactory
            ]
        );
        $this->className = Products::class;
    }

    public function testIsValueUrl()
    {
        $testUrl = "http://test.com/media/item.png";
        $testFilename = 'item.png';
        $this->assertNotFalse($this->component->isValueUrl($testUrl));
        $this->assertFalse($this->component->isValueUrl($testFilename));
    }

    public function testDownloadFile()
    {
        $importerFactoryMock = $this->getMock(ImporterFactory::class);
        $productFactoryMock = $this->getMock(ProductFactory::class);
        $httpClientFactory = $this->getMock(
            ZendClientFactory::class,
            ['create'],
            [],
            '',
            false
        );
        $httpMock = $this->getMock(
            ZendClient::class,
            ['setUri', 'request', 'getBody'],
            [],
            '',
            false
        );
        $httpMock->expects($this->any())->method('setUri')->willReturnSelf();
        $httpMock->expects($this->any())->method('request')->willReturnSelf();
        $httpMock->expects($this->any())->method('getBody')->willReturn('testbinarycontent');

        $httpClientFactory->expects($this->atLeastOnce())->method('create')->willReturn($httpMock);

        $mockFileFactory = $this->getMock(
            FileFactory::class,
            ['create'],
            [],
            '',
            false
        );

        $productsTest = $this->testObjectManager->getObject(
            Products::class,
            [
                'importerFactory' => $importerFactoryMock,
                'productFactory' => $productFactoryMock,
                'httpClientFactory' => $httpClientFactory,
                'fileFactory' => $mockFileFactory
            ]
        );

        $this->assertEquals('testbinarycontent', $productsTest->downloadFile('http://test.com/media/item.png'));
    }

    public function testGetFileName()
    {
        $testUrl = "http://test.com/media/item.png";

        $importerFactoryMock = $this->getMock(ImporterFactory::class);
        $productFactoryMock = $this->getMock(ProductFactory::class);
        $httpClientFactory = $this->getMock(
            ZendClientFactory::class,
            [],
            [],
            '',
            false
        );
        $mockFileFactory = $this->getMock(
            FileFactory::class,
            [],
            [],
            '',
            false
        );

        $productsTest = $this->testObjectManager->getObject(
            Products::class,
            [
                'importerFactory' => $importerFactoryMock,
                'productFactory' => $productFactoryMock,
                'httpClientFactory' => $httpClientFactory,
                'fileFactory' => $mockFileFactory
            ]
        );
        $this->assertEquals('item.png', $productsTest->getFileName($testUrl));
    }
}
