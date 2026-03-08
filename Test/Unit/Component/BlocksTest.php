<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Blocks;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Cms\Model\Block;
use Magento\Cms\Model\ResourceModel\Block\Collection;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Component\Blocks
 */
class BlocksTest extends TestCase
{
    private Blocks $component;

    /** @var BlockInterfaceFactory&MockObject */
    private BlockInterfaceFactory $blockFactory;

    /** @var BlockRepositoryInterface&MockObject */
    private BlockRepositoryInterface $blockRepository;

    /** @var Store&MockObject */
    private Store $storeManager;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    /** @var DriverInterface&MockObject */
    private DriverInterface $driver;

    protected function setUp(): void
    {
        $this->blockFactory    = $this->createMock(BlockInterfaceFactory::class);
        $this->blockRepository = $this->createMock(BlockRepositoryInterface::class);
        $this->storeManager    = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->log             = $this->createMock(LoggerInterface::class);
        $this->driver          = $this->createMock(DriverInterface::class);

        $this->component = new Blocks(
            $this->blockFactory,
            $this->blockRepository,
            $this->storeManager,
            $this->log,
            $this->driver
        );
    }

    // ── Alias ─────────────────────────────────────────────────────────────────

    public function testGetAliasReturnsBlocks(): void
    {
        $this->assertSame('blocks', $this->component->getAlias());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a Block mock with configurable collection count.
     * The same mock object is returned by blockFactory->create() for both the
     * collection-lookup call and the new-block creation call.
     */
    private function blockMockWithCollection(int $collectionCount): Block&MockObject
    {
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'count', 'getFirstItem'])
            ->getMock();
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('count')->willReturn($collectionCount);

        $block = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection', 'getId', 'getData', 'setData', 'setIdentifier', 'unsetData'])
            ->addMethods(['setStoreId', 'setStores'])
            ->getMock();
        $block->method('getCollection')->willReturn($collection);

        return $block;
    }

    // ── New block ─────────────────────────────────────────────────────────────

    public function testExecuteCreatesNewBlockWhenNoneExist(): void
    {
        $block = $this->blockMockWithCollection(0); // no existing blocks
        $block->method('getData')->willReturn(null);

        $this->blockFactory->method('create')->willReturn($block);

        $block->expects($this->once())->method('setIdentifier')->with('my-block');
        $this->blockRepository->expects($this->once())->method('save')->with($block);

        $this->component->execute([
            'my-block' => [
                'block' => [['title' => 'My Block']],
            ],
        ]);
    }

    // ── Existing block, data changed ──────────────────────────────────────────

    public function testExecuteUpdatesExistingBlockWhenDataDiffers(): void
    {
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'count', 'getFirstItem'])
            ->getMock();
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('count')->willReturn(1);

        $existingBlock = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getData', 'setData', 'setIdentifier', 'unsetData'])
            ->addMethods(['setStoreId', 'setStores'])
            ->getMock();
        $existingBlock->method('getId')->willReturn(9);
        // existing title differs → triggers setData + save
        $existingBlock->method('getData')->willReturn('Old Title');

        $collection->method('getFirstItem')->willReturn($existingBlock);

        $lookupBlock = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $lookupBlock->method('getCollection')->willReturn($collection);

        $this->blockFactory->method('create')->willReturn($lookupBlock);

        $existingBlock->expects($this->atLeastOnce())->method('setData');
        $this->blockRepository->expects($this->once())->method('save')->with($existingBlock);

        $this->component->execute([
            'existing-block' => [
                'block' => [['title' => 'New Title']],
            ],
        ]);
    }

    // ── Existing block, data unchanged ────────────────────────────────────────

    public function testExecuteSkipsSaveWhenBlockDataUnchanged(): void
    {
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToFilter', 'count', 'getFirstItem'])
            ->getMock();
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('count')->willReturn(1);

        $existingBlock = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getData', 'setData', 'setIdentifier', 'unsetData'])
            ->addMethods(['setStoreId', 'setStores'])
            ->getMock();
        $existingBlock->method('getId')->willReturn(9);
        // getData returns the same value as in the data array → no change
        $existingBlock->method('getData')->willReturn('Same Title');

        $collection->method('getFirstItem')->willReturn($existingBlock);

        $lookupBlock = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCollection'])
            ->getMock();
        $lookupBlock->method('getCollection')->willReturn($collection);

        $this->blockFactory->method('create')->willReturn($lookupBlock);

        $this->blockRepository->expects($this->never())->method('save');

        $this->component->execute([
            'same-block' => [
                'block' => [['title' => 'Same Title']],
            ],
        ]);
    }

    // ── Source file ───────────────────────────────────────────────────────────

    public function testExecuteLoadsContentFromSourceFile(): void
    {
        $block = $this->blockMockWithCollection(0);
        $block->method('getData')->willReturn(null);

        $this->blockFactory->method('create')->willReturn($block);

        $this->driver->expects($this->once())
            ->method('fileGetContents')
            ->with($this->stringContains('content.html'))
            ->willReturn('<p>Hello</p>');

        $this->component->execute([
            'source-block' => [
                'block' => [['source' => 'content.html']],
            ],
        ]);
    }

    // ── Store not found ───────────────────────────────────────────────────────

    public function testExecuteLogsErrorWhenStoreNotFound(): void
    {
        $block = $this->blockMockWithCollection(0);
        $block->method('getData')->willReturn(null);

        $this->blockFactory->method('create')->willReturn($block);

        // Store load returns a store with no ID → ComponentException
        $mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId'])
            ->getMock();
        $mockStore->method('load')->willReturnSelf();
        $mockStore->method('getId')->willReturn(null);
        $this->storeManager->method('load')->willReturn($mockStore);

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('No store'));

        $this->component->execute([
            'store-block' => [
                'block' => [['title' => 'My Block', 'stores' => ['missing_store']]],
            ],
        ]);
    }
}
