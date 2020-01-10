<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Products;
use FireGento\FastSimpleImport\Model\ImporterFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product;
use CtiDigital\Configurator\Component\Product\Image;
use CtiDigital\Configurator\Component\Product\AttributeOption;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Eav\Model\Entity\Attribute;

class ProductsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Products
     */
    private $products;

    /**
     * @var ImporterFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerFactory;

    /**
     * @var ProductFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    private $productFactory;

    /**
     * @var Image|\PHPUnit\Framework\MockObject\MockObject
     */
    private $image;

    /**
     * @var AttributeOption|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeOption;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $log;

    protected function setUp()
    {
        $this->importerFactory = $this->getMockBuilder(ImporterFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productFactory = $this->getMockBuilder(ProductFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->image = $this->getMockBuilder(Image::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeOption = $this->getMockBuilder(AttributeOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->products = new Products(
            $this->importerFactory,
            $this->productFactory,
            $this->image,
            $this->attributeOption,
            $this->log
        );
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
        $this->assertEquals($expected, $this->products->getSkuColumnIndex($columns));
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

        $this->assertEquals($expected, $this->products->getAttributesFromCsv($importData));
    }

    public function testIsConfigurable()
    {
        $importData = [
            'product_type' => 'configurable'
        ];
        $this->assertTrue($this->products->isConfigurable($importData));
    }

    public function testIsNotAConfigurable()
    {
        $importData = [
            'product_type' => 'simple'
        ];
        $this->assertFalse($this->products->isConfigurable($importData));
    }

    public function testConstructVariations()
    {
        $configurableData = [
            'associated_products' => '1,2',
            'configurable_attributes' => 'colour,size,style',
        ];

        $expected = 'sku=1;colour=Blue;size=Medium;style=Loose|sku=2;colour=Red;size=Small;style=Loose';

        $productAColourMock = $this->createMockAttribute('colour', 'Blue');
        $productASizeMock = $this->createMockAttribute('size', 'Medium');
        $productAStyleMock = $this->createMockAttribute('style', 'Loose');
        $productBColourMock = $this->createMockAttribute('colour', 'Red');
        $productBSizeMock = $this->createMockAttribute('size', 'Small');
        $productBStyleMock = $this->createMockAttribute('style', 'Loose');

        $simpleMockA = $this->createProduct(1);

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

        $simpleMockA->method('hasData')
            ->will(
                $this->onConsecutiveCalls(
                    'Blue',
                    'Medium',
                    'Loose'
                )
            );

        $simpleMockB = $this->createProduct(2);

        $simpleMockB->method('getAttribute')
            ->will(
                $this->onConsecutiveCalls(
                    $productBColourMock,
                    $productBSizeMock,
                    $productBStyleMock
                )
            );

        $simpleMockB->method('hasData')
            ->will(
                $this->onConsecutiveCalls(
                    'Red',
                    'Small',
                    'Loose'
                )
            );

        $this->productFactory->expects($this->at(0))
            ->method('create')
            ->willReturn($simpleMockA);

        $this->productFactory->expects($this->at(1))
            ->method('create')
            ->willReturn($simpleMockB);

        $this->assertEquals($expected, $this->products->constructConfigurableVariations($configurableData));
    }

    public function testIsStockSet()
    {
        $testData = [
            'sku' => 1,
            'is_in_stock' => 1,
            'qty' => 1
        ];
        $this->assertTrue($this->products->isStockSpecified($testData));
    }

    public function testStockIsNotSet()
    {
        $testData = [
            'sku' => 1,
            'name' => 'Test'
        ];
        $this->assertFalse($this->products->isStockSpecified($testData));
    }

    public function testSetStock()
    {
        $testData = [
            'sku' => 1,
            'name' => 'Test',
            'is_in_stock' => 1
        ];
        $expectedData = [
            'sku' => 1,
            'name' => 'Test',
            'is_in_stock' => 1,
            'qty' => 1
        ];
        $this->assertEquals($expectedData, $this->products->setStock($testData));
    }

    public function testNotSetStock()
    {
        $testData = [
            'sku' => 1,
            'name' => 'Test',
            'is_in_stock' => 0
        ];
        $expectedData = [
            'sku' => 1,
            'name' => 'Test',
            'is_in_stock' => 0,
        ];
        $this->assertEquals($expectedData, $this->products->setStock($testData));
    }

    private function createProduct($productId)
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasData', 'getSku', 'getIdBySku', 'load', 'getId', 'getResource', 'getAttribute'])
            ->getMock();
        $productMock->expects($this->any())
            ->method('getId')
            ->willReturn($productId);
        $productMock->expects($this->any())
            ->method('getIdBySku')
            ->willReturnSelf();
        $productMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $productMock->expects($this->any())
            ->method('getResource')
            ->willReturnSelf();
        return $productMock;
    }

    private function createMockAttribute($attributeCode, $value)
    {
        $attr = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFrontend', 'getValue', 'getAttributeCode'])
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
