<?php
namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

/**
 * Class OrderStatuses
 * @package CtiDigital\Configurator\Component
 */
class OrderStatuses extends YamlComponentAbstract
{

    /**
     * Component alias
     *
     * @var string
     */
    protected $alias = 'order_statuses';

    /**
     * Component name
     *
     * @var string
     */
    protected $name = 'Order Statuses';

    /**
     * Component description
     *
     * @var string
     */
    protected $description = 'Component to create custom order statuses';

    /**
     * Status Factory
     *
     * @var StatusFactory
     */
    protected $statusFactory;

    /**
     * Status Resource Factory
     *
     * @var StatusResourceFactory
     */
    protected $statusResource;

    /**
     * OrderStatuses constructor.
     * @param LoggerInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param StatusFactory $statusFactory
     * @param StatusResourceFactory $statusResource
     */
    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResource
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResource = $statusResource;
        parent::__construct($log, $objectManager);
    }

    /**
     * @param null $data
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function processData($data = null)
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
     * @param $statusSet
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function createOrderStatuses($statusSet)
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
}
