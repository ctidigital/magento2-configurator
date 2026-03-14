<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Api\AttributeOptionUpdateInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Attributes implements ComponentInterface
{
    protected string $alias = 'attributes';
    protected string $name = 'Attributes';
    protected string $description = 'Component to create/maintain attributes.';

    protected array $cachedAttributeConfig;

    protected array $attributeConfigMap = [
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

    protected array $skipCheck = [
        'option',
        'used_in_forms'
    ];

    protected string $entityTypeId = Product::ENTITY;

    protected bool $updateAttribute = true;

    protected bool $attributeExists = false;

    protected array $swatchMap = [];

    private array $optionCollection = [];

    public function __construct(
        protected readonly EavSetup $eavSetup,
        protected readonly AttributeRepositoryInterface $attributeRepository,
        private readonly LoggerInterface $log,
        protected readonly \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        protected readonly \Magento\Eav\Model\Config $eavConfig,
        private readonly AttributeOptionUpdateInterface $attributeOptionUpdate,
        private readonly AttributeOptionInterfaceFactory $optionFactory,
        private readonly AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        private readonly StoreRepositoryInterface $storeRepository
    ) {
    }

    public function execute(mixed $data = null): void
    {
        try {
            foreach ($data['attributes'] as $attributeCode => $attributeConfiguration) {
                $this->processAttribute($attributeCode, $attributeConfiguration);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function processAttribute(mixed $attributeCode, array $attributeConfig): void
    {
        $this->updateAttribute = true;
        $this->attributeExists = false;
        $attributeArray = $this->eavSetup->getAttribute($this->entityTypeId, $attributeCode);
        if ($attributeArray && $attributeArray['attribute_id']) {
            $this->handleExistingAttribute($attributeCode, $attributeArray, $attributeConfig);
        }

        if ($this->updateAttribute) {
            if (!array_key_exists('user_defined', $attributeConfig)) {
                $attributeConfig['user_defined'] = 1;
            }

            if (isset($attributeConfig['product_types'])) {
                $attributeConfig['apply_to'] = implode(',', $attributeConfig['product_types']);
            }

            $swatch = $this->extractSwatchType($attributeConfig);

            $this->eavSetup->addAttribute($this->entityTypeId, $attributeCode, $attributeConfig);

            if ($this->attributeExists) {
                $this->log->logInfo(sprintf('Attribute %s updated.', $attributeCode));
            } else {
                $this->applySwatchConversion($attributeCode, $attributeConfig, $swatch);
                $this->log->logInfo(sprintf('Attribute %s created.', $attributeCode));
            }
        }

        if (isset($attributeConfig['option']['store_labels'])) {
            $this->processOptionStoreLabels($attributeCode, $attributeConfig['option']['store_labels']);
        }
    }

    private function handleExistingAttribute(mixed $attributeCode, array $attributeArray, array &$attributeConfig): void
    {
        $this->attributeExists = true;
        $this->log->logComment(sprintf('Attribute %s exists. Checking for updates.', $attributeCode));
        $this->updateAttribute = $this->checkForAttributeUpdates($attributeCode, $attributeArray, $attributeConfig);

        if (!isset($attributeConfig['option'])) {
            return;
        }

        $newAttributeOptions = $this->manageAttributeOptions($attributeCode, $attributeConfig['option']);
        if (!empty($newAttributeOptions)) {
            $this->updateAttribute = true;
        }
        $attributeConfig['option']['values'] = $newAttributeOptions;
    }

    /**
     * Apply per-store display labels to existing attribute options.
     *
     * YAML format:
     *   option:
     *     values: [W, B]
     *     store_labels:
     *       default:       # store code (or numeric store ID)
     *         W: White
     *         B: Black
     *
     * Each admin value is looked up by its store-0 label to find the option_id,
     * then AttributeOptionUpdateInterface::update() is called with all store labels
     * for that option aggregated into a single call (since the resource model does a
     * full DELETE + INSERT on eav_attribute_option_value for the option_id).
     */
    private function processOptionStoreLabels(string $attributeCode, array $storeLabels): void
    {
        try {
            $attribute = $this->attributeRepository->get($this->entityTypeId, $attributeCode);
        } catch (NoSuchEntityException $e) {
            $this->log->logError(sprintf("Attribute %s doesn't exist, skipping store labels.", $attributeCode));
            return;
        }

        $this->loadOptionCollection((int) $attribute->getId());

        // Build adminValue => optionId map from the option collection (store_id = 0 labels)
        $optionIdByAdminValue = [];
        foreach ($this->optionCollection[(int) $attribute->getId()] as $option) {
            $optionIdByAdminValue[$option->getValue()] = (int) $option->getId();
        }

        // Aggregate across all store entries: adminValue => [storeId => label, ...]
        // This ensures a single update() call per option, preserving all store labels.
        $labelsByOption = [];
        foreach ($storeLabels as $storeIdentifier => $optionMap) {
            $storeId = $this->resolveStoreId((string) $storeIdentifier);
            if ($storeId === null) {
                $this->log->logError(sprintf(
                    'Store "%s" not found, skipping its option labels for attribute %s.',
                    $storeIdentifier,
                    $attributeCode
                ));
                continue;
            }
            foreach ($optionMap as $adminValue => $storeLabel) {
                $labelsByOption[(string) $adminValue][$storeId] = (string) $storeLabel;
            }
        }

        foreach ($labelsByOption as $adminValue => $storeIdLabelMap) {
            if (!isset($optionIdByAdminValue[$adminValue])) {
                $this->log->logError(sprintf(
                    'Option "%s" not found on attribute %s, skipping store labels.',
                    $adminValue,
                    $attributeCode
                ), 1);
                continue;
            }

            $optionId = $optionIdByAdminValue[$adminValue];

            $storeOptionLabels = [];
            foreach ($storeIdLabelMap as $storeId => $label) {
                $storeOptionLabels[] = $this->optionLabelFactory->create()
                    ->setStoreId($storeId)
                    ->setLabel($label);
            }

            $option = $this->optionFactory->create()
                ->setLabel($adminValue)
                ->setStoreLabels($storeOptionLabels);

            try {
                $this->attributeOptionUpdate->update($this->entityTypeId, $attributeCode, $optionId, $option);
                $this->log->logComment(sprintf(
                    'Store labels updated for option "%s" on attribute %s.',
                    $adminValue,
                    $attributeCode
                ), 1);
            } catch (\Exception $e) {
                $this->log->logError(sprintf(
                    'Failed to update store labels for option "%s" on attribute %s: %s',
                    $adminValue,
                    $attributeCode,
                    $e->getMessage()
                ), 1);
            }
        }
    }

    /**
     * Resolve a store identifier (store code or numeric store ID) to an integer store ID.
     * Returns null if the store cannot be found.
     */
    private function resolveStoreId(string $storeIdentifier): ?int
    {
        if (is_numeric($storeIdentifier)) {
            return (int) $storeIdentifier;
        }

        try {
            return (int) $this->storeRepository->get($storeIdentifier)->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Extract the swatch type from config, mutating the config array in place.
     */
    private function extractSwatchType(array &$attributeConfig): string|false
    {
        if (!in_array($attributeConfig['input'] ?? '', ['swatch_text', 'swatch_visual'])) {
            return false;
        }

        $swatch = $attributeConfig['input'];
        $attributeConfig['input'] = 'select';
        $this->swatchMap = $attributeConfig['swatch'] ?? [];
        return $swatch;
    }

    private function applySwatchConversion(mixed $attributeCode, array $attributeConfig, string|false $swatch): void
    {
        if (!$swatch) {
            return;
        }

        if ($swatch === 'swatch_text') {
            $this->convertToTextSwatch($attributeCode, $attributeConfig);
            return;
        }

        $this->convertToVisualSwatch($attributeCode, $attributeConfig);
    }

    protected function checkForAttributeUpdates(mixed $attributeCode, array $attributeArray, array $attributeConfig): bool
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

    protected function mapAttributeConfig(string $name): string
    {
        if (isset($this->attributeConfigMap[$name])) {
            return $this->attributeConfigMap[$name];
        }
        return $name;
    }

    private function manageAttributeOptions(mixed $attributeCode, mixed $option): array
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

        $optionsToAdd = array_diff($option['values'] ?? [], $existingAttributeOptions);
        //$optionsToRemove = array_diff($existingAttributeOptions, $option['values']);

        return $optionsToAdd;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function convertToVisualSwatch(string $attributeName, array $attributeConfig): void
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeName);
        if (!$attribute) {
            return;
        }
        $attributeData = [];
        $attributeData['option'] = $this->addExistingOptions($attribute);
        $attributeData['frontend_input'] = 'select';
        $attributeData['swatch_input_type'] = 'visual';
        $attributeData['update_product_preview_image'] = 1;
        $attributeData['use_product_image_for_swatch'] = 0;
        $attributeData['optionvisual'] = $this->getOptionSwatch($attributeData, $attributeConfig['option']['values']);
        $attributeData['swatchvisual'] = $this->getOptionSwatchVisual($attributeData);
        $attribute->addData($attributeData);
        $attribute->save();
    }

    public function convertToTextSwatch(string $attributeName, array $attributeConfig): void
    {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeName);
        if (!$attribute) {
            return;
        }
        $attributeData = [];
        $attributeData['option'] = $this->addExistingOptions($attribute);
        $attributeData['frontend_input'] = 'select';
        $attributeData['swatch_input_type'] = 'text';
        $attributeData['update_product_preview_image'] = 1;
        $attributeData['use_product_image_for_swatch'] = 0;
        $attributeData['optiontext'] = $this->getOptionSwatch($attributeData, $attributeConfig['option']['values']);
        $attributeData['swatchtext'] = $this->getOptionSwatchText($attributeData);
        $attribute->addData($attributeData);
        $attribute->save();
    }

    private function getOptionSwatchVisual(array $attributeData): array
    {
        $optionSwatch = ['value' => []];
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            if (substr($optionValue, 0, 1) === '#' && strlen($optionValue) === 7) {
                $optionSwatch['value'][$optionKey] = $optionValue;
                continue;
            }
            if (!empty($this->swatchMap[$optionKey])) {
                $optionSwatch['value'][$optionKey] = $this->swatchMap[$optionKey];
                continue;
            }
            $optionSwatch['value'][$optionKey] = null;
        }
        return $optionSwatch;
    }

    protected function getOptionSwatch(array $attributeData, array $attributeOptions): array
    {
        $optionSwatch = ['order' => [], 'value' => [], 'delete' => []];
        $order = 0;
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $label = array_search($optionValue, $attributeOptions) ?? $optionValue;
            $optionSwatch['delete'][$optionKey] = '';
            $optionSwatch['order'][$optionKey] = (string)$order++;
            $optionSwatch['value'][$optionKey] = [$label, ''];
        }
        return $optionSwatch;
    }

    private function getOptionSwatchText(array $attributeData): array
    {
        $optionSwatch = ['value' => []];
        foreach ($attributeData['option'] as $optionKey => $optionValue) {
            $optionSwatch['value'][$optionKey] = [$optionValue, ''];
        }
        return $optionSwatch;
    }

    private function loadOptionCollection(mixed $attributeId): void
    {
        if (empty($this->optionCollection[$attributeId])) {
            $this->optionCollection[$attributeId] = $this->attrOptionCollectionFactory->create()
                ->setAttributeFilter($attributeId)
                ->setPositionOrder('asc', true)
                ->load();
        }
    }

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
