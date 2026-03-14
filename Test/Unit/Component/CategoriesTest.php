<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Categories;
use CtiDigital\Configurator\Model\CmsBlockResolver;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResource;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Block\Collection as BlockCollection;
use Magento\Eav\Model\Entity\Type as EntityType;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\ResourceModel\Group\Collection as GroupCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Component\Categories
 */
class CategoriesTest extends TestCase
{
    private Categories $component;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    /** @var ObjectManagerInterface&MockObject */
    private ObjectManagerInterface $objectManager;

    /** @var CategoryFactory&MockObject */
    private CategoryFactory $categoryFactory;

    /** @var GroupFactory&MockObject */
    private GroupFactory $groupFactory;

    /** @var DirectoryList&MockObject */
    private DirectoryList $dirList;

    /** @var BlockInterfaceFactory&MockObject */
    private BlockInterfaceFactory $blockFactory;

    /** @var DriverInterface&MockObject */
    private DriverInterface $driver;

    /** @var CmsBlockResolver&MockObject */
    private CmsBlockResolver $cmsBlockResolver;

    protected function setUp(): void
    {
        $this->log               = $this->createMock(LoggerInterface::class);
        $this->objectManager     = $this->createMock(ObjectManagerInterface::class);
        $this->categoryFactory   = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->groupFactory      = $this->getMockBuilder(GroupFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->dirList           = $this->createMock(DirectoryList::class);
        $this->blockFactory      = $this->getMockBuilder(BlockInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->driver            = $this->createMock(DriverInterface::class);
        $this->cmsBlockResolver = $this->createMock(CmsBlockResolver::class);

        $this->component = new Categories(
            $this->log,
            $this->objectManager,
            $this->categoryFactory,
            $this->groupFactory,
            $this->dirList,
            $this->blockFactory,
            $this->driver,
            $this->cmsBlockResolver
        );
    }

    // ── Alias ─────────────────────────────────────────────────────────────────

    public function testGetAliasReturnsCategories(): void
    {
        $this->assertSame('categories', $this->component->getAlias());
    }

    // ── getDefaultCategory ────────────────────────────────────────────────────

    public function testGetDefaultCategoryReturnsRootCategoryForMatchingGroup(): void
    {
        $rootCategory = $this->makeCategoryModel();

        $group = $this->getMockBuilder(Group::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRootCategoryId'])
            ->getMock();
        $group->method('getRootCategoryId')->willReturn(2);

        $groupCollection = $this->getMockBuilder(GroupCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getSize', 'getFirstItem'])
            ->getMock();
        $groupCollection->method('addFieldToFilter')->willReturnSelf();
        $groupCollection->method('getSize')->willReturn(1);
        $groupCollection->method('getFirstItem')->willReturn($group);

        $groupModel = $this->getMockBuilder(Group::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $groupModel->method('getCollection')->willReturn($groupCollection);

        $this->groupFactory->method('create')->willReturn($groupModel);

        $loadedCategory = $this->makeCategoryModel(id: 2);
        $this->categoryFactory->method('create')->willReturn($loadedCategory);

        // load() is called on the category — it returns itself
        $loadedCategory->method('load')->willReturn($loadedCategory);

        $result = $this->component->getDefaultCategory('Main Website Store');

        $this->assertInstanceOf(Category::class, $result);
    }

    public function testGetDefaultCategoryThrowsWhenNoGroupFound(): void
    {
        $this->expectException(\CtiDigital\Configurator\Exception\ComponentException::class);
        $this->expectExceptionMessageMatches('/Main Website Store/');

        $groupCollection = $this->getMockBuilder(GroupCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getSize'])
            ->getMock();
        $groupCollection->method('addFieldToFilter')->willReturnSelf();
        $groupCollection->method('getSize')->willReturn(0);

        $groupModel = $this->getMockBuilder(Group::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $groupModel->method('getCollection')->willReturn($groupCollection);

        $this->groupFactory->method('create')->willReturn($groupModel);

        $this->component->getDefaultCategory('Main Website Store');
    }

    public function testGetDefaultCategoryThrowsWhenMultipleGroupsFound(): void
    {
        $this->expectException(\CtiDigital\Configurator\Exception\ComponentException::class);
        $this->expectExceptionMessageMatches('/Multiple store groups/');

        $groupCollection = $this->getMockBuilder(GroupCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'getSize'])
            ->getMock();
        $groupCollection->method('addFieldToFilter')->willReturnSelf();
        $groupCollection->method('getSize')->willReturn(2);

        $groupModel = $this->getMockBuilder(Group::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $groupModel->method('getCollection')->willReturn($groupCollection);

        $this->groupFactory->method('create')->willReturn($groupModel);

        $this->component->getDefaultCategory('Main Website Store');
    }

    // ── landing_page: numeric passthrough ─────────────────────────────────────

    public function testLandingPageWithNumericValueDelegatesToResolver(): void
    {
        $dataCalls = [];
        [$parent] = $this->makeParentAndChildMocks($dataCalls);

        $this->cmsBlockResolver->method('resolve')->willReturn(42);

        $this->component->createOrUpdateCategory($parent, [
            ['name' => 'Test Cat', 'landing_page' => '42'],
        ]);

        $this->assertSame(42, $dataCalls['landing_page']);
    }

    public function testLandingPageWithIntegerValueDelegatesToResolver(): void
    {
        $dataCalls = [];
        [$parent] = $this->makeParentAndChildMocks($dataCalls);

        $this->cmsBlockResolver->method('resolve')->willReturn(7);

        $this->component->createOrUpdateCategory($parent, [
            ['name' => 'Test Cat', 'landing_page' => 7],
        ]);

        $this->assertSame(7, $dataCalls['landing_page']);
    }

    // ── landing_page: identifier lookup (found) ───────────────────────────────

    public function testLandingPageWithIdentifierResolvesBlockId(): void
    {
        $dataCalls = [];
        [$parent, $child] = $this->makeParentAndChildMocks($dataCalls);
        $child->method('getStoreId')->willReturn(0);

        $this->cmsBlockResolver->expects($this->once())
            ->method('resolve')
            ->with('my-cms-block', 0)
            ->willReturn(5);

        $this->component->createOrUpdateCategory($parent, [
            ['name' => 'Test Cat', 'landing_page' => 'my-cms-block'],
        ]);

        $this->assertSame(5, $dataCalls['landing_page']);
    }

    // ── landing_page: identifier lookup (not found) ───────────────────────────

    public function testLandingPageWithUnknownIdentifierLogsErrorAndSetsZero(): void
    {
        $dataCalls = [];
        [$parent, $child] = $this->makeParentAndChildMocks($dataCalls);
        $child->method('getStoreId')->willReturn(0);

        $this->cmsBlockResolver
            ->method('resolve')
            ->willReturn(0);

        $this->component->createOrUpdateCategory($parent, [
            ['name' => 'Test Cat', 'landing_page' => 'unknown-block'],
        ]);

        $this->assertSame(0, $dataCalls['landing_page']);
    }

    // ── cms_block: legacy title lookup (found) ────────────────────────────────

    public function testCmsBlockSetsBothLandingPageAndDisplayMode(): void
    {
        $dataCalls = [];
        [$parent, $child] = $this->makeParentAndChildMocks($dataCalls);

        $blockModel = $this->makeCmsBlockModelWithId(3);
        $this->blockFactory->method('create')->willReturn($blockModel);

        $this->component->createOrUpdateCategory($parent, [
            ['name' => 'Test Cat', 'cms_block' => 'All Stores'],
        ]);

        $this->assertSame(3, $dataCalls['landing_page']);
        $this->assertSame('PRODUCTS_AND_PAGE', $dataCalls['display_mode']);
    }

    // ── cms_block: legacy title lookup (not found) ────────────────────────────

    public function testCmsBlockLogsErrorWhenBlockNotFoundByTitle(): void
    {
        $dataCalls = [];
        [$parent, $child] = $this->makeParentAndChildMocks($dataCalls);

        $blockModel = $this->makeCmsBlockModelWithId(null); // no ID → not found
        $this->blockFactory->method('create')->willReturn($blockModel);

        $this->log->expects($this->atLeastOnce())
            ->method('logError')
            ->with($this->stringContains('Missing Block'));

        $this->component->createOrUpdateCategory($parent, [
            ['name' => 'Test Cat', 'cms_block' => 'Missing Block'],
        ]);

        $this->assertArrayNotHasKey('landing_page', $dataCalls);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Creates a parent Category mock and the child Category that the collection returns.
     * $dataCalls is populated via reference as setData() is called on the child.
     *
     * @return array{0: Category&MockObject, 1: Category&MockObject}
     */
    private function makeParentAndChildMocks(array &$dataCalls): array
    {
        $parent = $this->makeCategoryModel(id: 2, path: '1/2');

        $child = $this->makeCategoryModel(id: null, storeId: 0);

        // Capture every setData() call so tests can inspect the result.
        // Must return $child to satisfy the `static` return type on the mocked method.
        $child->method('setData')
            ->willReturnCallback(function (string $key, mixed $value) use (&$dataCalls, $child) {
                $dataCalls[$key] = $value;
                return $child;
            });

        $collection = $this->getMockBuilder(CategoryCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->getMock();
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getFirstItem')->willReturn($child);

        $lookupModel = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $lookupModel->method('getCollection')->willReturn($collection);

        $this->categoryFactory->method('create')->willReturn($lookupModel);

        return [$parent, $child];
    }

    /**
     * Build a Category mock with optional id, path, and storeId stubs.
     */
    private function makeCategoryModel(
        ?int $id = null,
        string $path = '1/2',
        int $storeId = 0
    ): Category&MockObject {
        $entityType = $this->getMockBuilder(EntityType::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDefaultAttributeSetId'])
            ->getMock();
        $entityType->method('getDefaultAttributeSetId')->willReturn(4);

        $resource = $this->getMockBuilder(CategoryResource::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntityType'])
            ->getMock();
        $resource->method('getEntityType')->willReturn($entityType);

        $mock = $this->getMockBuilder(Category::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getId', 'getPath', 'getStoreId', 'getResource',
                'save', 'getName', 'getLevel', 'load', 'setData',
            ])
            ->getMock();

        $mock->method('getId')->willReturn($id);
        $mock->method('getPath')->willReturn($path);
        $mock->method('getStoreId')->willReturn($storeId);
        $mock->method('getResource')->willReturn($resource);
        $mock->method('getName')->willReturn('Test Cat');
        $mock->method('getLevel')->willReturn(2);
        $mock->method('load')->willReturnSelf();

        return $mock;
    }

    /**
     * Build a CMS Block mock wrapped in a factory-compatible model.
     * The returned object responds to getCollection() → collection → getFirstItem().
     */
    private function makeCmsBlockModelWithId(?int $blockId): Block&MockObject
    {
        $foundBlock = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $foundBlock->method('getId')->willReturn($blockId);

        $collection = $this->getMockBuilder(BlockCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'setPageSize', 'getFirstItem'])
            ->getMock();
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getFirstItem')->willReturn($foundBlock);

        $blockModel = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $blockModel->method('getCollection')->willReturn($collection);

        return $blockModel;
    }
}
