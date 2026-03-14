<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Model;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Model\CmsBlockResolver;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Cms\Model\GetBlockByIdentifier;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Model\CmsBlockResolver
 */
class CmsBlockResolverTest extends TestCase
{
    private CmsBlockResolver $resolver;

    /** @var GetBlockByIdentifier&MockObject */
    private GetBlockByIdentifier $blockByIdentifier;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    protected function setUp(): void
    {
        $this->blockByIdentifier = $this->createMock(GetBlockByIdentifier::class);
        $this->log               = $this->createMock(LoggerInterface::class);

        $this->resolver = new CmsBlockResolver($this->blockByIdentifier, $this->log);
    }

    // ── Numeric passthrough ───────────────────────────────────────────────────

    public function testNumericStringIsReturnedDirectlyWithoutLookup(): void
    {
        $this->blockByIdentifier->expects($this->never())->method('execute');

        $this->assertSame(42, $this->resolver->resolve('42'));
    }

    public function testIntegerIsReturnedDirectlyWithoutLookup(): void
    {
        $this->blockByIdentifier->expects($this->never())->method('execute');

        $this->assertSame(7, $this->resolver->resolve(7));
    }

    // ── Identifier lookup (found) ─────────────────────────────────────────────

    public function testIdentifierIsResolvedToBlockId(): void
    {
        $block = $this->createMock(BlockInterface::class);
        $block->method('getId')->willReturn('5');

        $this->blockByIdentifier->expects($this->once())
            ->method('execute')
            ->with('my-block', 0)
            ->willReturn($block);

        $this->assertSame(5, $this->resolver->resolve('my-block', 0));
    }

    public function testStoreIdIsPassedToLookup(): void
    {
        $block = $this->createMock(BlockInterface::class);
        $block->method('getId')->willReturn('3');

        $this->blockByIdentifier->expects($this->once())
            ->method('execute')
            ->with('store-block', 1)
            ->willReturn($block);

        $this->assertSame(3, $this->resolver->resolve('store-block', 1));
    }

    // ── Identifier lookup (not found) ─────────────────────────────────────────

    public function testUnknownIdentifierLogsErrorAndReturnsZero(): void
    {
        $this->blockByIdentifier
            ->method('execute')
            ->willThrowException(new NoSuchEntityException(__('No such entity.')));

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('unknown-block'));

        $this->assertSame(0, $this->resolver->resolve('unknown-block'));
    }
}
