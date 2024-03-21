<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Attributes implements ComponentInterface
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
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory
     */
    protected $attrOptionCollectionFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

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

    /**
     * @var array
     */
    protected $swatchMap = [];

    /**
     * Attributes constructor.
     * @param EavSetup $eavSetup
     * @param AttributeRepositoryInterface $attributeRepository
     * @param LoggerInterface $log
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     */
    public function __construct(
        EavSetup $eavSetup,
        AttributeRepositoryInterface $attributeRepository,
        LoggerInterface $log,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        $this->eavSetup = $eavSetup;
        $this->attributeRepository = $attributeRepository;
        $this->log = $log;
        $this->attrOptionCollectionFactory = $attrOptionCollectionFactory;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param array $attributeConfigurationData
     */
    public function execute($attributeConfigurationData = null)
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
            //swatch functionality
            $swatch = false;
            if (in_array($attributeConfig['input'], ['swatch_text', 'swatch_visual'])){
                $swatch = $attributeConfig['input'];
                $attributeConfig['input'] = 'select';
                $this->swatchMap =  $attributeConfig['swatch'] ?? [];
            }
            //swatch functionality
            $this->eavSetup->addAttribute(
                $this->entityTypeId,
                $attributeCode,
                $attributeConfig
            );

            if ($this->attributeExists) {
                $this->log->logInfo(sprintf('Attribute %s updated.', $attributeCode));
                return;
            }
            //swatch functionality
            if ($swatch) {
                if ($swatch === 'swatch_text'){
                    $this->convertToTextSwatch($attributeCode, $attributeConfig);
                } else {
                    $this->convertToVisualSwatch($attributeCode, $attributeConfig);

                }
            }
            //swatch functionality

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
            return [];
        }

        // Loop through existing attributes options
        $existingAttributeOptions = [];
        foreach ($attributeOptions as $attributeOption) {
            $value = $attributeOption->getLabel();
            $existingAttributeOptions[] = $value;
        }

        $optionsToAdd = array_diff($option['values'], $existingAttributeOptions);
        //$optionsToRemove = array_diff($existingAttributeOptions, $option['values']);

        return $optionsToAdd;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $attributeName
     * @param array $attributeConfig
     * @return void
     */
    public function convertToVisualSwatch(string $attributeName, array $attributeConfig)
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeName);
        if (!$attribute) {
            return;
        }
        $attributeData['option'] = $this->addExistingOptions($attribute);
        $attributeData['frontend_input'] = 'select';
        $attributeData['swatch_input_type'] = 'visual';
        $attributeData['update_product_preview_image'] = 1;
        $attributeData['use_product_image_for_swatch'] = 0;
        $attributeData['optionvisual'] = $this->getOptionSwatch($attributeData, $attributeConfig['option']['values']);
        $attributeData['defaultvisual'] = $this->getOptionDefaultVisual($attributeData);
        $attributeData['swatchvisual'] = $this->getOptionSwatchVisual($attributeData);
        $attribute->addData($attributeData);
        $attribute->save();
    }

    /**
     * @param string $attributeName
     * @param array $attributeConfig
     * @return void
     */
    public function convertToTextSwatch(string $attributeName, array $attributeConfig)
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeName);
        if (!$attribute) {
            return;
        }
        $attributeData['option'] = $this->addExistingOptions($attribute);
        $attributeData['frontend_input'] = 'select';
        $attributeData['swatch_input_type'] = 'text';
        $attributeData['update_product_preview_image'] = 1;
        $attributeData['use_product_image_for_swatch'] = 0;
        $attributeData['optiontext'] = $this->getOptionSwatch($attributeData, $attributeConfig['option']['values']);
        $attributeData['defaulttext'] = $this->getOptionDefaultText($attributeData);
        $attributeData['swatchtext'] = $this->getOptionSwatchText($attributeData);
        $attribute->addData($attributeData);
        $attribute->save();
    }


    /**
     * @param array $attributeData
     * @return array
     */
    private function getOptionSwatchVisual(array $attributeData): array
    {
        $optionSwatch = ['value' => []];
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            if (substr($optionValue, 0, 1) == '#' && strlen($optionValue) == 7) {
                $optionSwatch['value'][$optionKey] = $optionValue;
            } else if (!empty($this->swatchMap[$optionKey])) {
                $optionSwatch['value'][$optionKey] = $this->swatchMap[$optionKey];
            } else {
                $optionSwatch['value'][$optionKey] = null;
            }
        }
        return $optionSwatch;
    }

    /**
     * @param array $attributeData
     * @param array $attributeOptions
     * @return array
     */
    protected function getOptionSwatch(array $attributeData, array $attributeOptions): array
    {
        $optionSwatch = ['order' => [], 'value' => [], 'delete' => []];
        $i = 0;
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $label = array_search($optionValue, $attributeOptions) ?? $optionValue;
            $optionSwatch['delete'][$optionKey] = '';
            $optionSwatch['order'][$optionKey] = (string)$i++;
            $optionSwatch['value'][$optionKey] = [$label, ''];
        }
        return $optionSwatch;
    }

    /**
     * @param array $attributeData
     * @return array
     */
    private function getOptionDefaultVisual(array $attributeData): array
    {
        $optionSwatch = $this->getOptionSwatchVisual($attributeData);
        return [array_keys($optionSwatch['value'])[0]];
    }

    /**
     * @param array $attributeData
     * @return array
     */
    private function getOptionSwatchText(array $attributeData): array
    {
        $optionSwatch = ['value' => []];
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['value'][$optionKey] = [$optionValue, ''];
        }
        return $optionSwatch;
    }

    /**
     * @param array $attributeData
     * @return array
     */
    private function getOptionDefaultText(array $attributeData): array
    {
        $optionSwatch = $this->getOptionSwatchText($attributeData);
        return [array_keys($optionSwatch['value'])[0]];
    }

    /**
     * @param $attributeId
     * @return void
     */
    private function loadOptionCollection($attributeId)
    {
        if (empty($this->optionCollection[$attributeId])) {
            $this->optionCollection[$attributeId] = $this->attrOptionCollectionFactory->create()
                ->setAttributeFilter($attributeId)
                ->setPositionOrder('asc', true)
                ->load();
        }
    }

    /**
     * @param Attribute $attribute
     * @return array
     */
    private function addExistingOptions(Attribute $attribute): array
    {
        $options = [];
        $attributeId = $attribute->getId();
        if ($attributeId) {
            $this->loadOptionCollection($attributeId);
            /** @var \Magento\Eav\Model\Entity\Attribute\Option $option */
            foreach ($this->optionCollection[$attributeId] as $option) {
                $options[$option->getId()] = $option->getValue();
            }
        }

        return $options;
    }
}
