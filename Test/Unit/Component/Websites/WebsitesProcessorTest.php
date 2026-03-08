<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component\Websites;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Websites\WebsitesProcessor;
use Magento\Framework\Event\ManagerInterface;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Component\Websites\WebsitesProcessor
 */
class WebsitesProcessorTest extends TestCase
{
    private WebsitesProcessor $processor;

    /** @var IndexerFactory&MockObject */
    private IndexerFactory $indexer;

    /** @var ManagerInterface&MockObject */
    private ManagerInterface $eventManager;

    /** @var WebsiteFactory&MockObject */
    private WebsiteFactory $websiteFactory;

    /** @var StoreFactory&MockObject */
    private StoreFactory $storeFactory;

    /** @var GroupFactory&MockObject */
    private GroupFactory $groupFactory;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    protected function setUp(): void
    {
        $this->indexer        = $this->createMock(IndexerFactory::class);
        $this->eventManager   = $this->createMock(ManagerInterface::class);
        $this->websiteFactory = $this->createMock(WebsiteFactory::class);
        $this->storeFactory   = $this->createMock(StoreFactory::class);
        $this->groupFactory   = $this->createMock(GroupFactory::class);
        $this->log            = $this->createMock(LoggerInterface::class);

        $this->processor = new WebsitesProcessor(
            $this->indexer,
            $this->eventManager,
            $this->websiteFactory,
            $this->storeFactory,
            $this->groupFactory,
            $this->log
        );
    }

    // ── Fluent interface ──────────────────────────────────────────────────────

    public function testSetDataReturnsSameInstance(): void
    {
        $result = $this->processor->setData(['websites' => []]);
        $this->assertSame($this->processor, $result);
    }

    public function testSetConfigReturnsSameInstanceAndIsNoOp(): void
    {
        $result = $this->processor->setConfig(['any' => 'config']);
        $this->assertSame($this->processor, $result);
    }

    // ── process() guard clause ────────────────────────────────────────────────

    public function testProcessLogsErrorWhenNoWebsitesKeyInData(): void
    {
        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('No websites found'));

        $this->processor->setData([])->process();
    }

    public function testProcessLogsErrorWhenDataIsEmpty(): void
    {
        $this->log->expects($this->once())
            ->method('logError');

        $this->processor->setData([])->process();
    }

    // ── process() orchestration ───────────────────────────────────────────────

    public function testProcessCallsWebsiteFactoryForEachWebsite(): void
    {
        $mockWebsite = $this->createMock(\Magento\Store\Model\Website::class);
        $mockWebsite->method('load')->willReturnSelf();
        $mockWebsite->method('getId')->willReturn(1); // website exists, no save needed
        $mockWebsite->method('getData')->willReturn([]);
        $mockWebsite->method('setData')->willReturnSelf();

        $mockGroup = $this->createMock(\Magento\Store\Model\Group::class);
        $mockGroup->method('load')->willReturnSelf();
        $mockGroup->method('getId')->willReturn(1); // group exists
        $mockGroup->method('getData')->willReturn([]);
        $mockGroup->method('getName')->willReturn('Main Store');
        $mockGroup->method('getDefaultStoreId')->willReturn(1);

        $mockStore = $this->createMock(\Magento\Store\Model\Store::class);
        $mockStore->method('load')->willReturnSelf();
        $mockStore->method('getId')->willReturn(1); // store exists
        $mockStore->method('getData')->willReturn([]);
        $mockStore->method('getStoreGroupId')->willReturn(1);

        $this->websiteFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($mockWebsite);

        $this->groupFactory->method('create')->willReturn($mockGroup);

        // setDefaultStore uses storeFactory too
        $mockStore->method('getId')->willReturn(1);
        $mockGroup->method('getId')->willReturn(1);
        $mockStore->method('getStoreGroupId')->willReturn(1);
        $this->storeFactory->method('create')->willReturn($mockStore);

        $data = [
            'websites' => [
                'base'   => [
                    'store_groups' => [
                        [
                            'name'          => 'Main Store',
                            'default_store' => 'default',
                            'store_views'   => [
                                'default' => ['name' => 'Default Store View'],
                            ],
                        ],
                    ],
                ],
                'second' => [
                    'store_groups' => [
                        [
                            'name'          => 'Second Store',
                            'default_store' => 'second_en',
                            'store_views'   => [
                                'second_en' => ['name' => 'Second English'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->processor->setData($data)->process();
    }

    // ── New website creation ──────────────────────────────────────────────────

    public function testProcessCreatesNewWebsiteWhenNotFound(): void
    {
        $mockWebsite = $this->createMock(\Magento\Store\Model\Website::class);
        $mockWebsite->method('load')->willReturnSelf();
        $mockWebsite->method('getId')->willReturn(null); // does NOT exist
        $mockWebsite->method('getData')->willReturn([]);
        $mockWebsite->method('setData')->willReturnSelf();
        $mockWebsite->method('setCode')->willReturnSelf();
        $mockWebsite->method('getResource')->willReturnSelf();
        $mockWebsite->expects($this->once())->method('save');

        $this->websiteFactory->method('create')->willReturn($mockWebsite);
        $this->groupFactory->method('create')->willReturn($this->existingGroupMock());
        $this->storeFactory->method('create')->willReturn($this->existingStoreMock());

        // New website → reindex = true; provide a silent indexer mock.
        $this->indexer->method('create')->willReturn($this->silentIndexerMock());

        $this->processor->setData($this->singleWebsiteData())->process();
    }

    // ── Update existing website when data differs ─────────────────────────────

    public function testProcessUpdatesExistingWebsiteWhenDataDiffers(): void
    {
        $mockWebsite = $this->createMock(\Magento\Store\Model\Website::class);
        $mockWebsite->method('load')->willReturnSelf();
        $mockWebsite->method('getId')->willReturn(1); // exists
        $mockWebsite->method('getData')->willReturn(['name' => 'Old Name']);
        $mockWebsite->method('setData')->willReturnSelf();
        $mockWebsite->method('getResource')->willReturnSelf();
        $mockWebsite->expects($this->once())->method('save'); // data differs → must save

        $this->websiteFactory->method('create')->willReturn($mockWebsite);
        $this->groupFactory->method('create')->willReturn($this->existingGroupMock());
        $this->storeFactory->method('create')->willReturn($this->existingStoreMock());

        $data = [
            'websites' => [
                'base' => [
                    'name'         => 'New Name',
                    'store_groups' => [
                        [
                            'name'          => 'Main Store',
                            'default_store' => 'default',
                            'store_views'   => ['default' => ['name' => 'Default Store View']],
                        ],
                    ],
                ],
            ],
        ];

        $this->processor->setData($data)->process();
    }

    // ── New store group creation ───────────────────────────────────────────────

    public function testProcessCreatesNewStoreGroup(): void
    {
        $this->websiteFactory->method('create')->willReturn($this->existingWebsiteMock());

        $mockGroup = $this->createMock(\Magento\Store\Model\Group::class);
        $mockGroup->method('load')->willReturnSelf();
        $mockGroup->method('getId')->willReturn(null); // does NOT exist
        $mockGroup->method('getData')->willReturn([]);
        $mockGroup->method('setData')->willReturnSelf();
        $mockGroup->method('setWebsite')->willReturnSelf();
        $mockGroup->method('getName')->willReturn('Main Store');
        $mockGroup->method('getDefaultStoreId')->willReturn(1);
        $mockGroup->method('getResource')->willReturnSelf();
        $mockGroup->expects($this->atLeastOnce())->method('save');
        $this->groupFactory->method('create')->willReturn($mockGroup);

        // Use a clean store mock: getStoreGroupId() returns null to match the new group's
        // getId() = null, so processStoreView skips canSave and no getResource() call is needed.
        $mockStore = $this->createMock(\Magento\Store\Model\Store::class);
        $mockStore->method('load')->willReturnSelf();
        $mockStore->method('getId')->willReturn(1);      // store exists, no new-store save
        $mockStore->method('getData')->willReturn([]);
        $mockStore->method('getStoreGroupId')->willReturn(null); // null == null (new group getId) → no setGroup
        $this->storeFactory->method('create')->willReturn($mockStore);

        // New store group → reindex = true; provide a silent indexer mock.
        $this->indexer->method('create')->willReturn($this->silentIndexerMock());

        $this->processor->setData($this->singleWebsiteData())->process();
    }

    // ── New store view creation ───────────────────────────────────────────────

    public function testProcessCreatesNewStoreView(): void
    {
        $this->websiteFactory->method('create')->willReturn($this->existingWebsiteMock());
        $this->groupFactory->method('create')->willReturn($this->existingGroupMock());

        $mockStore = $this->createMock(\Magento\Store\Model\Store::class);
        $mockStore->method('load')->willReturnSelf();
        $mockStore->method('getId')->willReturn(null); // does NOT exist
        $mockStore->method('getData')->willReturn([]);
        $mockStore->method('setData')->willReturnSelf();
        $mockStore->method('setCode')->willReturnSelf();
        $mockStore->method('getStoreGroupId')->willReturn(1);
        $mockStore->method('setGroup')->willReturnSelf();
        $mockStore->method('getResource')->willReturnSelf();
        $mockStore->expects($this->atLeastOnce())->method('save');
        $this->storeFactory->method('create')->willReturn($mockStore);

        // New store view → reindex = true; provide a silent indexer mock.
        $this->indexer->method('create')->willReturn($this->silentIndexerMock());

        $this->processor->setData($this->singleWebsiteData())->process();
    }

    // ── store_add event dispatched when store view is created ─────────────────

    public function testProcessDispatchesStoreAddEventWhenStoreViewCreated(): void
    {
        $this->websiteFactory->method('create')->willReturn($this->existingWebsiteMock());
        $this->groupFactory->method('create')->willReturn($this->existingGroupMock());

        $mockStore = $this->createMock(\Magento\Store\Model\Store::class);
        $mockStore->method('load')->willReturnSelf();
        $mockStore->method('getId')->willReturn(null); // new → triggers dispatch
        $mockStore->method('getData')->willReturn([]);
        $mockStore->method('setData')->willReturnSelf();
        $mockStore->method('setCode')->willReturnSelf();
        $mockStore->method('getStoreGroupId')->willReturn(1);
        $mockStore->method('setGroup')->willReturnSelf();
        $mockStore->method('getResource')->willReturnSelf();
        $mockStore->method('save');
        $this->storeFactory->method('create')->willReturn($mockStore);

        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with('store_add', $this->arrayHasKey('store'));

        // New store view → reindex = true; provide a silent indexer mock.
        $this->indexer->method('create')->willReturn($this->silentIndexerMock());

        $this->processor->setData($this->singleWebsiteData())->process();
    }

    // ── setDefaultStore: no-op when already correct ───────────────────────────

    public function testSetDefaultStoreIsNoopWhenDefaultAlreadyCorrect(): void
    {
        $this->websiteFactory->method('create')->willReturn($this->existingWebsiteMock());

        $mockGroup = $this->createMock(\Magento\Store\Model\Group::class);
        $mockGroup->method('load')->willReturnSelf();
        $mockGroup->method('getId')->willReturn(1);
        $mockGroup->method('getData')->willReturn([]);
        $mockGroup->method('getName')->willReturn('Main Store');
        $mockGroup->method('getDefaultStoreId')->willReturn(99); // matches storeView->getId()
        $mockGroup->method('getResource')->willReturnSelf();
        $mockGroup->expects($this->never())->method('save'); // no change → no save
        $this->groupFactory->method('create')->willReturn($mockGroup);

        $mockStore = $this->createMock(\Magento\Store\Model\Store::class);
        $mockStore->method('load')->willReturnSelf();
        $mockStore->method('getId')->willReturn(99); // same as group's defaultStoreId
        $mockStore->method('getData')->willReturn([]);
        $mockStore->method('getStoreGroupId')->willReturn(1);
        $this->storeFactory->method('create')->willReturn($mockStore);

        $this->processor->setData($this->singleWebsiteData())->process();
    }

    // ── setDefaultStore: saves when default changed ───────────────────────────

    public function testSetDefaultStoreUpdatesWhenDefaultChanged(): void
    {
        $this->websiteFactory->method('create')->willReturn($this->existingWebsiteMock());

        $mockGroup = $this->createMock(\Magento\Store\Model\Group::class);
        $mockGroup->method('load')->willReturnSelf();
        $mockGroup->method('getId')->willReturn(1);
        $mockGroup->method('getData')->willReturn([]);
        $mockGroup->method('getName')->willReturn('Main Store');
        $mockGroup->method('getDefaultStoreId')->willReturn(0); // differs from storeView
        $mockGroup->method('getResource')->willReturnSelf();
        $mockGroup->expects($this->once())->method('setDefaultStoreId')->with(99);
        $mockGroup->expects($this->once())->method('save');
        $this->groupFactory->method('create')->willReturn($mockGroup);

        $mockStore = $this->createMock(\Magento\Store\Model\Store::class);
        $mockStore->method('load')->willReturnSelf();
        $mockStore->method('getId')->willReturn(99); // differs from group's default (0)
        $mockStore->method('getData')->willReturn([]);
        $mockStore->method('getStoreGroupId')->willReturn(1);
        $mockStore->method('getCode')->willReturn('default');
        $this->storeFactory->method('create')->willReturn($mockStore);

        $this->processor->setData($this->singleWebsiteData())->process();
    }

    // ── Reindex triggered when new entities created ───────────────────────────

    public function testProcessTriggersReindexWhenNewEntitiesCreated(): void
    {
        $mockWebsite = $this->createMock(\Magento\Store\Model\Website::class);
        $mockWebsite->method('load')->willReturnSelf();
        $mockWebsite->method('getId')->willReturn(null); // new → sets $reindex = true
        $mockWebsite->method('getData')->willReturn([]);
        $mockWebsite->method('setData')->willReturnSelf();
        $mockWebsite->method('setCode')->willReturnSelf();
        $mockWebsite->method('getResource')->willReturnSelf();
        $mockWebsite->method('save');
        $this->websiteFactory->method('create')->willReturn($mockWebsite);

        $this->groupFactory->method('create')->willReturn($this->existingGroupMock());
        $this->storeFactory->method('create')->willReturn($this->existingStoreMock());

        $mockIndexer = $this->createMock(\Magento\Indexer\Model\Indexer::class);
        $mockIndexer->expects($this->once())->method('load')->with('catalog_product_price')->willReturnSelf();
        $mockIndexer->expects($this->once())->method('reindexAll');
        $this->indexer->expects($this->once())->method('create')->willReturn($mockIndexer);

        $this->processor->setData($this->singleWebsiteData())->process();
    }

    // ── Shared fixtures ───────────────────────────────────────────────────────

    /**
     * A silent (non-asserting) Indexer mock for tests that trigger reindex as a side-effect
     * but whose primary assertion is elsewhere.
     */
    private function silentIndexerMock(): \Magento\Indexer\Model\Indexer
    {
        $mock = $this->createMock(\Magento\Indexer\Model\Indexer::class);
        $mock->method('load')->willReturnSelf();
        return $mock;
    }

    private function singleWebsiteData(): array
    {
        return [
            'websites' => [
                'base' => [
                    'store_groups' => [
                        [
                            'name'          => 'Main Store',
                            'default_store' => 'default',
                            'store_views'   => ['default' => ['name' => 'Default Store View']],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function existingWebsiteMock(): \Magento\Store\Model\Website
    {
        $mock = $this->createMock(\Magento\Store\Model\Website::class);
        $mock->method('load')->willReturnSelf();
        $mock->method('getId')->willReturn(1);
        $mock->method('getData')->willReturn([]);
        $mock->method('setData')->willReturnSelf();
        return $mock;
    }

    private function existingGroupMock(): \Magento\Store\Model\Group
    {
        $mock = $this->createMock(\Magento\Store\Model\Group::class);
        $mock->method('load')->willReturnSelf();
        $mock->method('getId')->willReturn(1);
        $mock->method('getData')->willReturn([]);
        $mock->method('getName')->willReturn('Main Store');
        $mock->method('getDefaultStoreId')->willReturn(1);
        return $mock;
    }

    private function existingStoreMock(): \Magento\Store\Model\Store
    {
        $mock = $this->createMock(\Magento\Store\Model\Store::class);
        $mock->method('load')->willReturnSelf();
        $mock->method('getId')->willReturn(1);
        $mock->method('getData')->willReturn([]);
        $mock->method('getStoreGroupId')->willReturn(1);
        return $mock;
    }
}
