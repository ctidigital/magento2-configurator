<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Sequence;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\SalesSequence\Model\Builder;
use Magento\SalesSequence\Model\Config as SequenceConfig;
use Magento\SalesSequence\Model\EntityPool;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    private Sequence $sequence;

    /** @var Builder|\PHPUnit\Framework\MockObject\MockObject */
    private $builder;

    /** @var EntityPool|\PHPUnit\Framework\MockObject\MockObject */
    private $entityPool;

    /** @var SequenceConfig|\PHPUnit\Framework\MockObject\MockObject */
    private $sequenceConfig;

    /** @var StoreRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $storeRepository;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var Store|\PHPUnit\Framework\MockObject\MockObject */
    private $storeMock;

    protected function setUp(): void
    {
        $this->builder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'setPrefix', 'setSuffix', 'setStartValue', 'setStoreId',
                'setStep', 'setWarningValue', 'setMaxValue', 'setEntityType', 'create',
            ])
            ->getMock();

        // Configure all fluent setters to return self so the chain doesn't break.
        foreach (['setPrefix', 'setSuffix', 'setStartValue', 'setStoreId', 'setStep', 'setWarningValue', 'setMaxValue', 'setEntityType'] as $method) {
            $this->builder->method($method)->willReturnSelf();
        }

        $this->entityPool = $this->getMockBuilder(EntityPool::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEntities'])
            ->getMock();

        $this->sequenceConfig = $this->getMockBuilder(SequenceConfig::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->storeRepository = $this->getMockBuilder(StoreRepositoryInterface::class)
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getCode'])
            ->getMock();
        $this->storeMock->method('getId')->willReturn(1);
        $this->storeMock->method('getCode')->willReturn('default');

        $this->sequence = new Sequence(
            $this->builder,
            $this->entityPool,
            $this->sequenceConfig,
            $this->storeRepository,
            $this->logger
        );
    }

    public function testGetAliasReturnsSequence(): void
    {
        $this->assertSame('sequence', $this->sequence->getAlias());
    }

    public function testExecuteThrowsComponentExceptionWhenStoresKeyAbsent(): void
    {
        $this->expectException(ComponentException::class);

        $this->sequence->execute([]);
    }

    public function testExecuteCreatesSequenceTableForEachEntityType(): void
    {
        $this->entityPool->method('getEntities')->willReturn(['order', 'invoice']);
        $this->sequenceConfig->method('get')->willReturn(null);
        $this->storeRepository->method('get')->willReturn($this->storeMock);

        $this->builder->expects($this->exactly(2))->method('create');

        $this->sequence->execute(['stores' => ['default' => []]]);
    }

    public function testExecuteUsesOverrideValuesWhenProvided(): void
    {
        $this->entityPool->method('getEntities')->willReturn(['order']);
        $this->sequenceConfig->method('get')->willReturn(null);
        $this->storeRepository->method('get')->willReturn($this->storeMock);

        $this->builder->expects($this->once())
            ->method('setPrefix')
            ->with('X')
            ->willReturnSelf();
        $this->builder->expects($this->once())->method('create');

        $this->sequence->execute(['stores' => ['default' => ['prefix' => 'X']]]);
    }

    public function testExecuteLogsErrorWhenBuilderThrowsException(): void
    {
        $this->entityPool->method('getEntities')->willReturn(['order']);
        $this->sequenceConfig->method('get')->willReturn(null);
        $this->storeRepository->method('get')->willReturn($this->storeMock);

        $this->builder->method('create')->willThrowException(new \Exception('DB error'));

        $this->logger->expects($this->atLeastOnce())->method('logError');

        $this->sequence->execute(['stores' => ['default' => []]]);
    }
}
