<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Model\Import\Source;

use CtiDigital\Configurator\Api\ImportAdapterFactoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\ImportExport\Model\Import\AbstractSource;

/**
 * Creates {@see ArrayAdapter} instances via the object manager so the adapter
 * itself stays a plain value object while still benefiting from DI.
 */
class ArrayAdapterFactory implements ImportAdapterFactoryInterface
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly string $instanceName = ArrayAdapter::class
    ) {
    }

    public function create(array $data = []): AbstractSource
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
