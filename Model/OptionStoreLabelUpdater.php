<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Eav\Api\AttributeOptionUpdateInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Applies per-store display labels to existing EAV attribute options.
 *
 * Extracted from Attributes to keep the component within PHPMD coupling limits.
 */
class OptionStoreLabelUpdater
{
    private array $optionCollection = [];

    public function __construct(
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly AttributeOptionUpdateInterface $attributeOptionUpdate,
        private readonly AttributeOptionInterfaceFactory $optionFactory,
        private readonly AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        private readonly StoreRepositoryInterface $storeRepository,
        // phpcs:ignore Generic.Files.LineLength
        private readonly \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        private readonly LoggerInterface $log
    ) {
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
    public function update(string $entityTypeId, string $attributeCode, array $storeLabels): void
    {
        try {
            $attribute = $this->attributeRepository->get($entityTypeId, $attributeCode);
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

            $this->applyOptionLabels(
                $entityTypeId,
                $attributeCode,
                $adminValue,
                $optionIdByAdminValue[$adminValue],
                $storeIdLabelMap
            );
        }
    }

    /**
     * Build label objects and call the update API for a single option.
     */
    private function applyOptionLabels(
        string $entityTypeId,
        string $attributeCode,
        string $adminValue,
        int $optionId,
        array $storeIdLabelMap
    ): void {
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
            $this->attributeOptionUpdate->update($entityTypeId, $attributeCode, $optionId, $option);
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

    private function loadOptionCollection(int $attributeId): void
    {
        if (empty($this->optionCollection[$attributeId])) {
            $this->optionCollection[$attributeId] = $this->attrOptionCollectionFactory->create()
                ->setAttributeFilter($attributeId)
                ->setPositionOrder('asc', true)
                ->load();
        }
    }
}
