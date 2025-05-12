<?php

namespace CtiDigital\Configurator\Test\Unit\Component\Product;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Product\AttributeOption;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Model\Entity\Attribute\Option;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

class AttributeOptionTest extends TestCase
{
    /**
     * @var ProductAttributeRepositoryInterface | PHPUnit_Framework_MockObject_MockObject
     */
    private $attrRepository;

    /**
     * @var AttributeOptionManagementInterface|MockObject
     */
    private $attrOptionManagement;

    /**
     * @var AttributeOptionLabelInterfaceFactory|MockObject
     */
    private $attrOptionLabelFact;

    /**
     * @var AttributeOptionInterfaceFactory|MockObject
     */
    private $attrOptionFactory;

    /**
     * @var LoggerInterface|MockObject
     */
    private $log;

    /**
     * @var AttributeOption
     */
    private $attributeOption;

    protected function setUp(): void
    {
        $this->attrRepository = $this->getMockBuilder(ProductAttributeRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getList', 'get', 'save', 'delete', 'deleteById', 'getCustomAttributesMetadata'])
            ->getMock();

        $this->attrOptionManagement = $this->getMockBuilder(AttributeOptionManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attrOptionLabelFact = $this->getMockBuilder(AttributeOptionLabelInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attrOptionFactory = $this->getMockBuilder(AttributeOptionInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeOption = new AttributeOption(
            $this->attrRepository,
            $this->attrOptionManagement,
            $this->attrOptionLabelFact,
            $this->attrOptionFactory,
            $this->log
        );
    }

    public function testAttributeIsDropdown()
    {
        /**
         * @var Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', []);
        $this->attrRepository->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertTrue($this->attributeOption->isOptionAttribute('colour'));
    }

    public function testAttributeIsMultiSelect()
    {
        /**
         * @var Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'multiselect', []);
        $this->attrRepository->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertTrue($this->attributeOption->isOptionAttribute('colour'));
    }

    public function testAttributeIsNotAllowed()
    {
        /**
         * @var Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'text', null);
        $this->attrRepository->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertFalse($this->attributeOption->isOptionAttribute('colour'));
    }

    public function testAttributeBackendModelIsNotProcessed()
    {
        /**
         * @var Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', []);
        $attributeMock->expects($this->once())
            ->method('getBackendModel')
            ->willReturn('/test/');
        $this->attrRepository->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertFalse($this->attributeOption->isOptionAttribute('colour'));
    }

    public function testOptionValueDoesNotExist()
    {
        /**
         * @var Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', ['Red']);
        $this->attrRepository->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertFalse($this->attributeOption->isOptionValueExists('colour', 'White'));
    }

    public function testOptionValueExists()
    {
        /**
         * @var Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', ['Red']);
        $this->attrRepository->expects($this->once())
            ->method('get')
            ->willReturn($attributeMock);
        $this->assertTrue($this->attributeOption->isOptionValueExists('colour', 'Red'));
    }

    public function testNewOptionIsNotDuplicated()
    {
        /**
         * @var Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', ['Red']);
        $this->attrRepository->expects($this->once())
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
         * @var Attribute $attributeMock
         */
        $attributeMock = $this->createMockAttribute('colour', 'select', ['Red', 'Green']);
        $this->attrRepository->expects($this->once())
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
         * @var PHPUnit_Framework_MockObject_MockObject $attributeMock
         */
        $attributeMock = $this->getMockBuilder(Attribute::class)
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
                $option = $this->getMockBuilder(Option::class)
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
