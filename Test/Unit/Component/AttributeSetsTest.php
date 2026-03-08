<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\AttributeSets;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\AttributeSetRepository;
use Magento\Eav\Model\Entity\Attribute\Set as AttributeSetModel;
use Magento\Eav\Setup\EavSetup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Component\AttributeSets
 */
class AttributeSetsTest extends TestCase
{
    private AttributeSets $component;

    /** @var EavSetup&MockObject */
    private EavSetup $eavSetup;

    /** @var AttributeSetRepository&MockObject */
    private AttributeSetRepository $attributeSetRepository;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    protected function setUp(): void
    {
        $this->eavSetup               = $this->getMockBuilder(EavSetup::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeSetRepository = $this->getMockBuilder(AttributeSetRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->log                    = $this->createMock(LoggerInterface::class);

        $this->component = new AttributeSets(
            $this->eavSetup,
            $this->attributeSetRepository,
            $this->log
        );
    }

    /** Returns a fresh AttributeSetModel mock with getId and initFromSkeleton configurable. */
    private function attributeSetMock(string $name = 'My Set', mixed $id = 1): AttributeSetModel&MockObject
    {
        $mock = $this->getMockBuilder(AttributeSetModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributeSetName', 'getId', 'initFromSkeleton'])
            ->getMock();
        $mock->method('getAttributeSetName')->willReturn($name);
        $mock->method('getId')->willReturn($id);
        return $mock;
    }

    // ── Alias ─────────────────────────────────────────────────────────────────

    public function testGetAliasReturnsAttributeSets(): void
    {
        $this->assertSame('attribute_sets', $this->component->getAlias());
    }

    // ── Basic create ──────────────────────────────────────────────────────────

    public function testExecuteCreatesAttributeSetWithoutInheritOrGroups(): void
    {
        $mockSet = $this->attributeSetMock();

        $this->eavSetup->expects($this->once())
            ->method('addAttributeSet')
            ->with(Product::ENTITY, 'My Set');

        $this->eavSetup->method('getAttributeSetId')->willReturn(5);
        $this->attributeSetRepository->method('get')->with(5)->willReturn($mockSet);
        $this->attributeSetRepository->expects($this->never())->method('save');

        $this->component->execute([
            'attribute_sets' => [
                ['name' => 'My Set'],
            ],
        ]);
    }

    // ── Inherit ───────────────────────────────────────────────────────────────

    public function testExecuteInheritsSkeletonWhenInheritKeyPresent(): void
    {
        $mockSet = $this->attributeSetMock();

        $this->eavSetup->method('getAttributeSetId')->willReturn(5);
        $this->attributeSetRepository->method('get')->willReturn($mockSet);

        // getAttributeSet used by getAttributeSetId() helper
        $this->eavSetup->method('getAttributeSet')
            ->willReturn(['attribute_set_id' => 3]);

        $mockSet->expects($this->once())->method('initFromSkeleton')->with(3);
        $this->attributeSetRepository->expects($this->once())->method('save')->with($mockSet);

        $this->component->execute([
            'attribute_sets' => [
                ['name' => 'My Set', 'inherit' => 'Default'],
            ],
        ]);
    }

    public function testExecuteDoesNotSaveWhenNoInheritKey(): void
    {
        $mockSet = $this->attributeSetMock();
        $this->eavSetup->method('getAttributeSetId')->willReturn(5);
        $this->attributeSetRepository->method('get')->willReturn($mockSet);

        $this->attributeSetRepository->expects($this->never())->method('save');

        $this->component->execute([
            'attribute_sets' => [
                ['name' => 'My Set'],
            ],
        ]);
    }

    // ── Groups ────────────────────────────────────────────────────────────────

    public function testExecuteAddsNewGroupWhenGroupNotFound(): void
    {
        $mockSet = $this->attributeSetMock('My Set');
        $this->eavSetup->method('getAttributeSetId')->willReturn(5);
        $this->attributeSetRepository->method('get')->willReturn($mockSet);

        $this->eavSetup->method('convertToAttributeGroupCode')->willReturn('general');
        $this->eavSetup->method('getAttributeGroup')->willReturn(false);
        $this->eavSetup->method('getAttribute')->willReturn(['attribute_id' => 10]);

        $this->eavSetup->expects($this->once())
            ->method('addAttributeGroup')
            ->with(Product::ENTITY, 'My Set', 'General');

        $this->component->execute([
            'attribute_sets' => [
                [
                    'name'   => 'My Set',
                    'groups' => [
                        ['name' => 'General', 'attributes' => ['color']],
                    ],
                ],
            ],
        ]);
    }

    public function testExecuteLogsCommentWhenGroupAlreadyExists(): void
    {
        $mockSet = $this->attributeSetMock('My Set');
        $this->eavSetup->method('getAttributeSetId')->willReturn(5);
        $this->attributeSetRepository->method('get')->willReturn($mockSet);

        $this->eavSetup->method('convertToAttributeGroupCode')->willReturn('general');
        // Return truthy → group exists
        $this->eavSetup->method('getAttributeGroup')->willReturn(['attribute_group_id' => 7]);
        $this->eavSetup->method('getAttribute')->willReturn(['attribute_id' => 10]);

        $this->eavSetup->expects($this->never())->method('addAttributeGroup');
        $this->log->expects($this->atLeastOnce())->method('logComment');

        $this->component->execute([
            'attribute_sets' => [
                [
                    'name'   => 'My Set',
                    'groups' => [
                        ['name' => 'General', 'attributes' => ['color']],
                    ],
                ],
            ],
        ]);
    }

    // ── Error handling ────────────────────────────────────────────────────────

    public function testExecuteLogsErrorWhenAttributeInGroupNotFound(): void
    {
        $mockSet = $this->attributeSetMock('My Set');
        $this->eavSetup->method('getAttributeSetId')->willReturn(5);
        $this->attributeSetRepository->method('get')->willReturn($mockSet);

        $this->eavSetup->method('convertToAttributeGroupCode')->willReturn('general');
        $this->eavSetup->method('getAttributeGroup')->willReturn(false);
        // getAttribute returns empty → attribute does not exist → ComponentException
        $this->eavSetup->method('getAttribute')->willReturn([]);

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('does not exist'));

        $this->component->execute([
            'attribute_sets' => [
                [
                    'name'   => 'My Set',
                    'groups' => [
                        ['name' => 'General', 'attributes' => ['missing_attr']],
                    ],
                ],
            ],
        ]);
    }
}
