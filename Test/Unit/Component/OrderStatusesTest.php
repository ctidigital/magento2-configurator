<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\OrderStatuses;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;
use PHPUnit\Framework\TestCase;

class OrderStatusesTest extends TestCase
{
    private OrderStatuses $orderStatuses;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $log;

    /** @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $objectManager;

    /** @var StatusFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $statusFactory;

    /** @var StatusResourceFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $statusResourceFactory;

    protected function setUp(): void
    {
        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->getMock();

        $this->statusFactory = $this->getMockBuilder(StatusFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->statusResourceFactory = $this->getMockBuilder(StatusResourceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->orderStatuses = new OrderStatuses(
            $this->log,
            $this->objectManager,
            $this->statusFactory,
            $this->statusResourceFactory
        );
    }

    public function testGetAliasReturnsOrderStatuses(): void
    {
        $this->assertSame('order_statuses', $this->orderStatuses->getAlias());
    }

    public function testExecuteIsNoOpWhenKeyAbsent(): void
    {
        $this->statusFactory->expects($this->never())->method('create');

        $this->orderStatuses->execute([]);
    }

    public function testExecuteCreatesStatusAndSavesAndAssignsState(): void
    {
        $statusMock = $this->getMockBuilder(Status::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'assignState'])
            ->getMock();
        $statusMock->method('setData')->willReturnSelf();
        $statusMock->expects($this->once())
            ->method('assignState')
            ->with('processing', false, true);

        $resourceMock = $this->getMockBuilder(StatusResource::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();
        $resourceMock->expects($this->once())->method('save');

        $this->statusFactory->method('create')->willReturn($statusMock);
        $this->statusResourceFactory->method('create')->willReturn($resourceMock);

        $this->orderStatuses->execute([
            'order_statuses' => [
                [
                    'state'    => 'processing',
                    'statuses' => [
                        ['code' => 'custom_processing', 'name' => 'Custom Processing'],
                    ],
                ],
            ],
        ]);
    }

    public function testExecuteCreatesMultipleStatuses(): void
    {
        $statusMock = $this->getMockBuilder(Status::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setData', 'assignState'])
            ->getMock();
        $statusMock->method('setData')->willReturnSelf();
        $statusMock->method('assignState')->willReturnSelf();

        $resourceMock = $this->getMockBuilder(StatusResource::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();
        $resourceMock->expects($this->exactly(2))->method('save');

        $this->statusFactory->method('create')->willReturn($statusMock);
        $this->statusResourceFactory->method('create')->willReturn($resourceMock);

        $this->orderStatuses->execute([
            'order_statuses' => [
                [
                    'state'    => 'new',
                    'statuses' => [
                        ['code' => 'status_a', 'name' => 'Status A'],
                        ['code' => 'status_b', 'name' => 'Status B'],
                    ],
                ],
            ],
        ]);
    }
}
