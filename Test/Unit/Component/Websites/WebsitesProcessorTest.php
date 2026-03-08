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
}
