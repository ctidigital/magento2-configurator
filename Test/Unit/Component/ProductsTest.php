<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Products;
use Firegento\FastSimpleImport\Model\ImporterFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Response\Http\FileFactory;

class ProductsTest extends ComponentAbstractTestCase
{
    protected function componentSetUp()
    {
        $importerFactoryMock = $this->getMock(ImporterFactory::class, [], [], '', false);
        $productFactoryMock = $this->getMock(ProductFactory::class, [], [], '', false);
        $httpClientMock = $this->getMock(
            'Magento\Framework\HTTP\ZendClientFactory',
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

    public function testGetSkuColumnIndex()
    {
        $columns = [
            'attribute_set_code',
            'product_websites',
            'store_view_code',
            'product_type',
            'sku',
            'name',
            'short_description',
            'description'
        ];

        $expected = 4;
        $this->assertEquals($expected, $this->component->getSkuColumnIndex($columns));
    }

    public function testGetAttributesFromCsv()
    {
        $importData = [
            [
                'attribute_set_code',
                'product_websites',
                'store_view_code',
                'product_type',
                'sku',
                'name',
                'short_description',
                'description'
            ],
            [
                'Default',
                'base',
                'default',
                'configurable',
                '123',
                'Product A',
                'Short description',
                'Longer description'
            ]
        ];

        $expected = [
            'attribute_set_code',
            'product_websites',
            'store_view_code',
            'product_type',
            'sku',
            'name',
            'short_description',
            'description'
        ];

        $this->assertEquals($expected, $this->component->getAttributesFromCsv($importData));
    }

    public function testIsConfigurable()
    {
        $importData = [
            'product_type' => 'configurable'
        ];
        $this->assertTrue($this->component->isConfigurable($importData));
    }

    public function testIsNotAConfigurable()
    {
        $importData = [
            'product_type' => 'simple'
        ];
        $this->assertFalse($this->component->isConfigurable($importData));
    }
}
