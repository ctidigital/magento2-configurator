<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Products;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Response\Http\FileFactory;

class ProductsTest extends ComponentAbstractTestCase
{
    /**
     * @var ProductFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    private $productFactoryMock;

    protected function componentSetUp()
    {
        $importerFactoryMock = $this->getMockBuilder('Firegento\FastSimpleImport\Model\ImporterFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productFactoryMock = $this->getMockBuilder('Magento\Catalog\Model\ProductFactory')
            ->disableOriginalConstructor()
            ->getMock();

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
                'productFactory' => $this->productFactoryMock,
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

    public function testConstructConfigurableVariations()
    {
        $configurableData = [
            'associated_products' => '1,2',
            'configurable_attributes' => 'colour,size,style',
        ];

        $expected = 'sku=1,colour=Blue,size=Medium,style=Loose|sku=2,colour=Red,size=Small,style=Loose';

        $productAColourMock = $this->createMockAttribute('colour', 'Blue');
        $productASizeMock = $this->createMockAttribute('size', 'Medium');
        $productAStyleMock = $this->createMockAttribute('style', 'Loose');
        $productBColourMock = $this->createMockAttribute('colour', 'Red');
        $productBSizeMock = $this->createMockAttribute('size', 'Small');
        $productBStyleMock = $this->createMockAttribute('style', 'Loose');

        $simpleMockA = $this->getMockBuilder('Magento\Catalog\Model\Product')
            ->disableOriginalConstructor()
            ->setMethods(['getIdBySku', 'load', 'getId', 'getResource', 'getAttribute'])
            ->getMock();

        $simpleMockA->expects($this->any())
            ->method('getIdBySku')
            ->willReturnSelf();

        $simpleMockA->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $simpleMockA->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $simpleMockA->expects($this->any())
            ->method('getResource')
            ->willReturnSelf();

        $simpleMockA->method('getAttribute')
            ->will(
                $this->onConsecutiveCalls(
                    $productAColourMock,
                    $productASizeMock,
                    $productAStyleMock
                )
            );

        $simpleMockB = $this->getMockBuilder('Magento\Catalog\Model\Product')
            ->disableOriginalConstructor()
            ->setMethods(['getIdBySku', 'load', 'getId', 'getResource', 'getAttribute'])
            ->getMock();

        $simpleMockB->expects($this->any())
            ->method('getIdBySku')
            ->willReturnSelf();

        $simpleMockB->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $simpleMockB->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $simpleMockB->expects($this->any())
            ->method('getResource')
            ->willReturnSelf();

        $simpleMockB->method('getAttribute')
            ->will(
                $this->onConsecutiveCalls(
                    $productBColourMock,
                    $productBSizeMock,
                    $productBStyleMock
                )
            );

        $this->productFactoryMock->expects($this->at(0))
            ->method('create')
            ->willReturn($simpleMockA);

        $this->productFactoryMock->expects($this->at(1))
            ->method('create')
            ->willReturn($simpleMockB);

        $this->assertEquals($expected, $this->component->constructConfigurableVariations($configurableData));
    }

    private function createMockAttribute($attributeCode, $value)
    {
        $attr = $this->getMockBuilder('Magento\Eav\Model\Entity\Attribute')
            ->disableOriginalConstructor()
            ->setMethods(['getFrontend', 'getValue'])
            ->getMock();
        $attr->expects($this->once())
            ->method('getFrontend')
            ->willReturnSelf();
        $attr->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($attributeCode);
        $attr->expects($this->once())
            ->method('getValue')
            ->willReturn($value);
        return $attr;
    }
}
