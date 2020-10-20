<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\SalesSequence\Model\Builder;
use Magento\SalesSequence\Model\EntityPool;
use Magento\SalesSequence\Model\Config;
use Magento\Store\Api\StoreRepositoryInterface;
use CtiDigital\Configurator\Api\LoggerInterface;

class Sequence implements ComponentInterface
{
    /**
     * @var Builder
     */
    protected $sequenceBuilder;

    /**
     * @var EntityPool
     */
    protected $entityPool;

    /**
     * @var Config
     */
    protected $sequenceConfig;

    /**
     * @var StoreRepositoryInterface
     */
    protected $storeRepository;

    protected $logger;

    protected $alias = 'sequence';
    protected $description = 'Component to allow manual configuring of the sequence tables.';

    public function __construct(
        Builder $sequenceBuilder,
        EntityPool $entityPool,
        Config $sequenceConfig,
        StoreRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->sequenceBuilder = $sequenceBuilder;
        $this->entityPool = $entityPool;
        $this->sequenceConfig = $sequenceConfig;
        $this->storeRepository = $repository;
        $this->logger = $logger;
    }

    public function execute($data)
    {
        if (!isset($data['stores'])) {
            throw new ComponentException("No stores found.");
        }

        foreach ($data['stores'] as $code => $overrides) {
            try {
                $this->logger->logInfo(__("Starting creating sequence tables for %1", $code));
                $store = $this->storeRepository->get($code);
                $this->newSequenceTable($store, $overrides);
                $this->logger->logInfo(__("Finished creating sequence tables for %1", $code));
                // todo handle existing sequence tables
            } catch (\Exception $exception) {
                $this->logger->logError($exception->getMessage());
            }
        }
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function getDescription()
    {
        return $this->description;
    }

    protected function newSequenceTable($store, $overrides)
    {
        $configKeys = ['suffix', 'startValue', 'step', 'warningValue', 'maxValue'];
        $configValues = [];
        foreach ($configKeys as $key) {
            $configValues[$key] = $this->sequenceConfig->get($key);
            if (isset($overrides[$key])) {
                $configValues[$key] = $overrides[$key];
            }
        }

        // Prefix Value
        $configValues['prefix'] = $store->getId();
        if (isset($overrides['prefix'])) {
            $configValues['prefix'] = $overrides['prefix'];
        }

        foreach ($this->entityPool->getEntities() as $entityType) {
            try {
                $this->logger->logComment(__(
                    'Store: %1 '.
                    'Prefix: %2, '.
                    'Suffix: %3, '.
                    'Start Value: %4, '.
                    'Step: %5, '.
                    'Warning Value: %6, '.
                    'Max Value: %7, '.
                    'Entity Type: %8',
                    $store->getCode(),
                    $configValues['prefix'],
                    $configValues['suffix'],
                    $configValues['startValue'],
                    $configValues['step'],
                    $configValues['warningValue'],
                    $configValues['maxValue'],
                    $entityType
                ), 1);
                $this->sequenceBuilder->setPrefix($configValues['prefix'])
                    ->setSuffix($configValues['suffix'])
                    ->setStartValue($configValues['startValue'])
                    ->setStoreId($store->getId())
                    ->setStep($configValues['step'])
                    ->setWarningValue($configValues['warningValue'])
                    ->setMaxValue($configValues['maxValue'])
                    ->setEntityType($entityType)
                    ->create();
                $this->logger->logInfo(__("Sequence table created for %1", $entityType), 1);
            } catch (\Exception $exception) {
                $this->logger->logError($exception->getMessage());
            }
        }
    }
}
