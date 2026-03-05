<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

/**
 * Class OrderStatuses
 */
class OrderStatuses implements ComponentInterface
{
    /**
     * Component alias
     */
    public string $alias = 'order_statuses';

    /**
     * Component name
     */
    protected string $name = 'Order Statuses';

    /**
     * Component description
     */
    public string $description = 'Component to create custom order statuses';

    /**
     * OrderStatuses constructor.
     */
    public function __construct(
        protected readonly LoggerInterface $log,
        protected readonly ObjectManagerInterface $objectManager,
        protected readonly StatusFactory $statusFactory,
        protected readonly StatusResourceFactory $statusResource
    ) {}

    /**
     * @throws AlreadyExistsException
     */
    public function execute(mixed $data = null): void
    {
        if (isset($data['order_statuses'])) {
            foreach ($data['order_statuses'] as $statusSet) {
                try {
                    $this->createOrderStatuses($statusSet);
                } catch (ComponentException $e) {
                    $this->log->logError($e->getMessage());
                }
            }
        }
    }

    /**
     * @throws AlreadyExistsException
     */
    public function createOrderStatuses(mixed $statusSet): void
    {
        foreach ($statusSet['statuses'] as $statusData) {
            /** @var StatusResource $statusResource */
            $statusResource = $this->statusResource->create();
            /** @var Status $status */
            $status = $this->statusFactory->create();
            $status->setData([
                'status' => $statusData['code'],
                'label' => $statusData['name'],
            ]);

            try {
                $statusResource->save($status);
            } catch (ComponentException $e) {
                $this->log->logError($e->getMessage());
            }

            $status->assignState($statusSet['state'], false, true);

            $this->log->logInfo(
                sprintf('Order status %s created', $statusData['name'])
            );
        }
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
