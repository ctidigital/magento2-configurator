<?php
namespace CtiDigital\Configurator\Test\Unit\Component\Product;

use CtiDigital\Configurator\Model\Component\Product\AttributeOption;

class AttributeOptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Catalog\Api\ProductAttributeRepositoryInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $attrRepositoryMock;
    /**
     * @var AttributeOption
     */
    protected $attributeOption;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->attrRepositoryMock = $this->getMockBuilder('Magento\Catalog\Api\ProductAttributeRepositoryInterface')
            ->disableOriginalConstructor()
            ->setMethods(['getList', 'get', 'save', 'delete', 'deleteById', 'getCustomAttributesMetadata'])
            ->getMock();

        $this->attributeOption = $this->objectManager->getObject(
            AttributeOption::class,
            [
                'attributeRepository' => $this->attrRepositoryMock
            ]
        );
    }

    public function testAttributeIsDropdown()
    {
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', []);
        $this->attrRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertTrue($this->attributeOption->isOptionAttribute('colour'));
    }

    public function testAttributeIsMultiSelect()
    {
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'multiselect', []);
        $this->attrRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertTrue($this->attributeOption->isOptionAttribute('colour'));
    }

    public function testAttributeIsNotAllowed()
    {
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'text', null);
        $this->attrRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertFalse($this->attributeOption->isOptionAttribute('colour'));
    }

    public function testAttributeBackendModelIsNotProcessed()
    {
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', []);
        $attributeMock->expects($this->once())
            ->method('getBackendModel')
            ->willReturn('/test/');
        $this->attrRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertFalse($this->attributeOption->isOptionAttribute('colour'));
    }

    public function testOptionValueDoesNotExist()
    {
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', ['Red']);
        $this->attrRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertFalse($this->attributeOption->isOptionValueExists('colour', 'White'));
    }

    public function testOptionValueExists()
    {
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', ['Red']);
        $this->attrRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertTrue($this->attributeOption->isOptionValueExists('colour', 'Red'));
    }

    public function testNewOptionIsNotDuplicated()
    {
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', ['Red']);
        $this->attrRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $newValues = [
            'White',
            'White',
            'Green',
            'Red',
            'Red/Blue',
            'White'
        ];
        $expectedResult = [
            'colour' => [
                'White',
                'Green',
                'Red/Blue'
            ]
        ];
        foreach ($newValues as $newValue) {
            $this->attributeOption->processAttributeValues('colour', $newValue);
        }
        $this->assertEquals($expectedResult, $this->attributeOption->getNewOptions());

    }

    public function testAddOption()
    {
        /**
         * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', ['Red', 'Green']);
        $this->attrRepositoryMock->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $values = [
            'Red',
            'Green',
            'White'
        ];
        $expectedNewValues = ['colour' => ['White']];
        foreach ($values as $value) {
            $this->attributeOption->processAttributeValues('colour', $value);
        }
        $this->assertEquals($expectedNewValues, $this->attributeOption->getNewOptions());
    }

    private function createMockAttribute($code, $input, $values)
    {
        /**
         * @var \PHPUnit_Framework_MockObject_MockObject $attributeMock
         */
        $attributeMock = $this->getMockBuilder('Magento\Catalog\Model\ResourceModel\Eav\Attribute')
            ->disableOriginalConstructor()
            ->setMethods(['getAttributeCode', 'getFrontendInput', 'getOptions', 'getBackendModel', 'getIsUserDefined'])
            ->getMock();
        $attributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn($code);
        $attributeMock->expects($this->any())
            ->method('getFrontendInput')
            ->willReturn($input);
        if (is_array($values)) {
            $attributeOptions = [];
            foreach ($values as $attributeValue) {
                $option = $this->getMockBuilder('Magento\Eav\Model\Entity\Attribute\Option')
                    ->disableOriginalConstructor()
                    ->setMethods(['getLabel'])
                    ->getMock();
                $option->expects($this->any())
                    ->method('getLabel')
                    ->willReturn($attributeValue);
                $attributeOptions[] = $option;
            }
            $attributeMock->expects($this->any())
                ->method('getOptions')
                ->willReturn($attributeOptions);
        }
        $attributeMock->expects($this->any())
            ->method('getIsUserDefined')
            ->willReturn(true);
        return $attributeMock;
    }
}
