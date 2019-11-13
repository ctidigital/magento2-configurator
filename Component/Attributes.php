<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * @var array
     */
    protected $attributeConfigMap = [
        'label' => 'frontend_label',
        'type' => 'backend_type',
        'input' => 'frontend_input',
        'product_types' => 'apply_to',
        'required' => 'is_required',
        'source' => 'source_model',
        'backend' => 'backend_model',
        'frontend' => 'frontend_model',
        'searchable' => 'is_searchable',
        'global' => 'is_global',
        'filterable_in_search' => 'is_filterable_in_search',
        'unique' => 'is_unique',
        'visible_in_advanced_search' => 'is_visible_in_advanced_search',
        'comparable' => 'is_comparable',
        'visible_on_front' => 'is_visible_on_front',
        'filterable' => 'is_filterable',
        'user_defined' => 'is_user_defined',
        'default' => 'default_value',
        'used_for_promo_rules' => 'is_used_for_promo_rules'
    ];

    /**
     * @var array
     */
    protected $skipCheck = [
        'option',
        'used_in_forms'
    ];

    /**
     * @var string
     */
    protected $entityTypeId = Product::ENTITY;

    /**
     * @var bool 
     */
    protected $updateAttribute = true;

    /**
     * @var bool 
     */
    protected $attributeExists = false;

    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        EavSetup $eavSetup,
        AttributeRepositoryInterface $attributeRepository
    ) {
        parent::__construct($log, $objectManager);
        $this->eavSetup = $eavSetup;
        $this->attributeRepository = $attributeRepository;
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
        $this->updateAttribute = true;
        $this->attributeExists = false;
        $attributeArray = $this->eavSetup->getAttribute($this->entityTypeId, $attributeCode);
        if ($attributeArray && $attributeArray['attribute_id']) {
            $this->attributeExists = true;
            $this->log->logComment(sprintf('Attribute %s exists. Checking for updates.', $attributeCode));
            $this->updateAttribute = $this->checkForAttributeUpdates($attributeCode, $attributeArray, $attributeConfig);

            if (isset($attributeConfig['option'])) {
                $newAttributeOptions = $this->manageAttributeOptions($attributeCode, $attributeConfig['option']);
                if (!empty($newAttributeOptions)) {
                    $this->updateAttribute = true;
                }
                $attributeConfig['option']['values'] = $newAttributeOptions;
            }
        }

        if ($this->updateAttribute) {
            if (!array_key_exists('user_defined', $attributeConfig)) {
                $attributeConfig['user_defined'] = 1;
            }

            if (isset($attributeConfig['product_types'])) {
                $attributeConfig['apply_to'] = implode(',', $attributeConfig['product_types']);
            }

            $this->eavSetup->addAttribute(
                $this->entityTypeId,
                $attributeCode,
                $attributeConfig
            );

            if ($this->attributeExists) {
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
            if ($name == "product_types") {
                $value = implode(',', $value);
            }

            $name = $this->mapAttributeConfig($name);

            if (in_array($name, $this->skipCheck)) {
                continue;
            }
            if (!array_key_exists($name, $attributeArray)) {
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
        if (isset($this->attributeConfigMap[$name])) {
            return $this->attributeConfigMap[$name];
        }
        return $name;
    }

    private function manageAttributeOptions($attributeCode, $option)
    {
        $attributeOptions = [];
        try {
            $attribute = $this->attributeRepository->get($this->entityTypeId, $attributeCode);
            $attributeOptions = $attribute->getOptions();
        } catch (NoSuchEntityException $e) {
            $this->log->logError(sprintf(
                'Attribute %s doesn\'t exist',
                $attributeCode
            ));
        } catch (\TypeError $e) {
            $this->log->logError(sprintf(
                'Couldn\'t retrieve options for attribute %s.',
                $attributeCode
            ));
        } catch (\BadMethodCallException $e) {
            // @todo This should not happen. Rerunning customer attribute option appear to cause this exception.
            $this->log->logError(sprintf(
                'Couldn\'t retrieve options for attribute %s: %s',
                $attributeCode,
                $e->getMessage()
            ));
            return array();
        }

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
