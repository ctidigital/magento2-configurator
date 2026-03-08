<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Pages;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\Page;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Component\Pages
 */
class PagesTest extends TestCase
{
    private Pages $component;

    /** @var PageRepositoryInterface&MockObject */
    private PageRepositoryInterface $pageRepository;

    /** @var PageInterfaceFactory&MockObject */
    private PageInterfaceFactory $pageFactory;

    /** @var StoreRepositoryInterface&MockObject */
    private StoreRepositoryInterface $storeRepository;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    /** @var DriverInterface&MockObject */
    private DriverInterface $driver;

    protected function setUp(): void
    {
        $this->pageRepository  = $this->createMock(PageRepositoryInterface::class);
        $this->pageFactory     = $this->createMock(PageInterfaceFactory::class);
        $this->storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $this->log             = $this->createMock(LoggerInterface::class);
        $this->driver          = $this->createMock(DriverInterface::class);

        $this->component = new Pages(
            $this->pageRepository,
            $this->pageFactory,
            $this->storeRepository,
            $this->log,
            $this->driver
        );
    }

    // ── Alias ─────────────────────────────────────────────────────────────────

    public function testGetAliasReturnsPages(): void
    {
        $this->assertSame('pages', $this->component->getAlias());
    }

    // ── New page (no existing identifier) ─────────────────────────────────────

    public function testProcessPageCreatesNewPageWhenIdentifierNotFound(): void
    {
        $mockPage = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkIdentifier', 'setIdentifier', 'getData', 'setData', 'hasDataChanges'])
            ->addMethods(['setStores'])
            ->getMock();
        $mockPage->method('checkIdentifier')->willReturn(0);
        $mockPage->expects($this->once())->method('setIdentifier')->with('my-page');
        $mockPage->method('getData')->willReturn(null);
        $mockPage->method('setData')->willReturnSelf();
        $mockPage->method('setStores')->willReturnSelf();
        $mockPage->method('hasDataChanges')->willReturn(true);

        $this->pageFactory->method('create')->willReturn($mockPage);

        $this->pageRepository->expects($this->once())->method('save')->with($mockPage);

        $this->component->execute([
            'my-page' => [
                'page' => [['title' => 'My Page']],
            ],
        ]);
    }

    // ── Existing page (identifier found) ──────────────────────────────────────

    public function testProcessPageLoadsExistingPageWhenIdentifierFound(): void
    {
        $mockPageForCheck = $this->createMock(Page::class);
        $mockPageForCheck->method('checkIdentifier')->willReturn(7);

        $mockExistingPage = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'setData', 'hasDataChanges'])
            ->addMethods(['setStores'])
            ->getMock();
        $mockExistingPage->method('getData')->willReturn(null);
        $mockExistingPage->method('setData')->willReturnSelf();
        $mockExistingPage->method('setStores')->willReturnSelf();
        $mockExistingPage->method('hasDataChanges')->willReturn(false);

        // First create() is for checkIdentifier; there is no second create() call
        // because an existing page is returned from getById().
        $this->pageFactory->method('create')->willReturn($mockPageForCheck);

        $this->pageRepository->expects($this->once())
            ->method('getById')
            ->with(7)
            ->willReturn($mockExistingPage);

        $this->component->execute([
            'existing-page' => [
                'page' => [['title' => 'Existing Page']],
            ],
        ]);
    }

    // ── Skip save when no data changes ────────────────────────────────────────

    public function testProcessPageSkipsSaveWhenNoDataChanges(): void
    {
        $mockPage = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkIdentifier', 'setIdentifier', 'getData', 'setData', 'hasDataChanges'])
            ->addMethods(['setStores'])
            ->getMock();
        $mockPage->method('checkIdentifier')->willReturn(0);
        $mockPage->method('setIdentifier')->willReturnSelf();
        $mockPage->method('getData')->willReturn(null);
        $mockPage->method('setData')->willReturnSelf();
        $mockPage->method('setStores')->willReturnSelf();
        $mockPage->method('hasDataChanges')->willReturn(false);

        $this->pageFactory->method('create')->willReturn($mockPage);

        $this->pageRepository->expects($this->never())->method('save');

        $this->component->execute([
            'no-change-page' => [
                'page' => [['title' => 'Same Title']],
            ],
        ]);
    }

    // ── 'source' key reads file content ───────────────────────────────────────

    public function testProcessPageReadsContentFromSourceFile(): void
    {
        $mockPage = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkIdentifier', 'setIdentifier', 'getData', 'setData', 'hasDataChanges'])
            ->addMethods(['setStores'])
            ->getMock();
        $mockPage->method('checkIdentifier')->willReturn(0);
        $mockPage->method('setIdentifier')->willReturnSelf();
        $mockPage->method('getData')->willReturn(null);
        $mockPage->method('setData')->willReturnSelf();
        $mockPage->method('setStores')->willReturnSelf();
        $mockPage->method('hasDataChanges')->willReturn(false);

        $this->pageFactory->method('create')->willReturn($mockPage);

        $this->driver->expects($this->once())
            ->method('fileGetContents')
            ->with($this->stringContains('content.html'))
            ->willReturn('<p>Hello</p>');

        $this->component->execute([
            'source-page' => [
                'page' => [['title' => 'Source Page', 'source' => 'content.html']],
            ],
        ]);
    }

    // ── Missing required 'title' field ────────────────────────────────────────

    public function testProcessPageLogsErrorWhenTitleIsMissing(): void
    {
        $mockPage = $this->createMock(Page::class);
        $mockPage->method('checkIdentifier')->willReturn(0);
        $mockPage->method('setIdentifier')->willReturnSelf();

        $this->pageFactory->method('create')->willReturn($mockPage);

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('title'));

        $this->pageRepository->expects($this->never())->method('save');

        $this->component->execute([
            'missing-title-page' => [
                'page' => [[/* no title */]],
            ],
        ]);
    }
}
