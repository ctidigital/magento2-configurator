<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Attributes
 * @package CtiDigital\Configurator\Model\Component
 * @SuppressWarnings(PHPMD.LongVariable)
 */
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

    /**
     * @var Product\Attribute\Repository
     */
    protected $productAttributeRepository;

    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        EavSetupFactory $eavSetupFactory,
        Product\Attribute\Repository $repository
    ) {
        parent::__construct($log, $objectManager);
        $this->eavSetup = $eavSetupFactory->create();
        $this->productAttributeRepository = $repository;
    }

    /**
     * @param array $attributeConfigurationData
     */
    protected function processData($attributeConfigurationData = null)
    {
        try {
            foreach ($attributeConfigurationData['attributes'] as $attributeCode => $attributeConfiguration) {
                $this->processAttribute($attributeCode, $attributeConfiguration);
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
        $updateAttribute = true;
        $attributeExists = false;
        $attributeArray = $this->eavSetup->getAttribute(Product::ENTITY, $attributeCode);
        if ($attributeArray && $attributeArray['attribute_id']) {
            $attributeExists = true;
            $this->log->logComment(sprintf('Attribute %s exists. Checking for updates.', $attributeCode));
            $updateAttribute = $this->checkForAttributeUpdates($attributeCode, $attributeArray, $attributeConfig);

            if (isset($attributeConfig['option'])) {
                $newAttributeOptions = $this->manageAttributeOptions($attributeCode, $attributeConfig['option']);
                $attributeConfig['option']['values'] = $newAttributeOptions;
            }
        }

        if ($updateAttribute) {

            $attributeConfig['user_defined'] = 1;

            if (isset($attributeConfig['product_types'])) {
                $attributeConfig['apply_to'] = implode(',', $attributeConfig['product_types']);
            }

            $this->eavSetup->addAttribute(
                Product::ENTITY,
                $attributeCode,
                $attributeConfig
            );

            if ($attributeExists) {
                $this->log->logInfo(sprintf('Attribute %s updated.', $attributeCode));
                return;
            }

            $this->log->logInfo(sprintf('Attribute %s created.', $attributeCode));
        }
    }

    protected function checkForAttributeUpdates($attributeCode, $attributeArray, $attributeConfig)
    {
        $requiresUpdate = false;
        $nest = 1;
        foreach ($attributeConfig as $name => $value) {

            $name = $this->mapAttributeConfig($name);

            if ($name == 'option') {
                continue;
            }

            if (!isset($attributeArray[$name])) {
                $this->log->logError(sprintf(
                    'Attribute %s type %s does not exist or is not mapped',
                    $attributeCode,
                    $name
                ), $nest);
                continue;
            }

            if ($attributeArray[$name] != $value) {
                $this->log->logInfo(sprintf(
                    'Update required for %s as %s is "%s" but should be "%s"',
                    $attributeCode,
                    $name,
                    $attributeArray[$name],
                    $value
                ), $nest);

                $requiresUpdate = true;

                continue;
            }

            $this->log->logComment(sprintf(
                'No Update required for %s as %s is still "%s"',
                $attributeCode,
                $name,
                $value
            ), $nest);
        }

        return $requiresUpdate;
    }

    protected function mapAttributeConfig($name)
    {
        if ($name == 'label') {
            $name = 'frontend_label';
        }

        if ($name == 'type') {
            $name = 'backend_type';
        }

        if ($name == 'input') {
            $name = 'frontend_input';
        }

        if ($name == 'product_types') {
            $name = 'apply_to';
        }
        return $name;
    }

    private function manageAttributeOptions($attributeCode, $option)
    {
        $attributeOptions = $this->productAttributeRepository->get($attributeCode)->getOptions();

        // Loop through existing attributes options
        $existingAttributeOptions = array();
        foreach ($attributeOptions as $attributeOption) {
            $value = $attributeOption->getLabel();
            $existingAttributeOptions[] = $value;
        }

        $optionsToAdd = array_diff($option['values'], $existingAttributeOptions);
        //$optionsToRemove = array_diff($existingAttributeOptions, $option['values']);


        return $optionsToAdd;
    }
}
