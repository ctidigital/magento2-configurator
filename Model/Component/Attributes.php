<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\ObjectManagerInterface;

class Attributes extends YamlComponentAbstract
{

    protected $alias = 'attributes';
    protected $name = 'Attributes';
    protected $description = 'Component to create/maintain attributes.';

    /**
     * @var EavSetup
     */
    protected $eavSetup;

    /**
     * @var array
     */
    protected $cachedAttributeConfig;

    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        EavSetup $eavSetup
    ) {

        parent::__construct($log, $objectManager);

        $this->eavSetup = $eavSetup;
    }

    /**
     * @param array $attributeConfigurationData
     */
    protected function processData($attributeConfigurationData = null)
    {
        try {
            foreach ($attributeConfigurationData['attributes'] as $_attributeCode => $_attributeConfiguration) {
                $this->processAttribute($_attributeCode, $_attributeConfiguration);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * @param $attributeCode
     * @param $attributeConfig
     */
    protected function processAttribute($attributeCode, array $attributeConfig)
    {
        $attributeConfig['user_defined'] = 1;

        $this->eavSetup->addAttribute(
            Product::ENTITY,
            $attributeCode,
            $attributeConfig
        );
    }
}
