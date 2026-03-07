<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component\Product;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Catalog\Model\Product;
use CtiDigital\Configurator\Api\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class AttributeOption
{
    private array $attributes = [];

    private array $attributeValues = [];

    private array $allowedInputs = ['select', 'multiselect'];

    private array $ignoreAttributes = [
        'visibility',
        'tax_class_id'
    ];

    private array $newValues = [];

    public function __construct(
        protected readonly ProductAttributeRepositoryInterface $attributeRepository,
        protected readonly AttributeOptionManagementInterface $attrOptionManagement,
        protected readonly AttributeOptionLabelInterfaceFactory $labelFactory,
        protected readonly AttributeOptionInterfaceFactory $optionFactory,
        protected readonly LoggerInterface $log
    ) {}

    public function processAttributeValues(mixed $code, mixed $value): void
    {
        try {
            if ($this->isOptionAttribute($code) === false) {
                return;
            }
            if ($this->isValidValue($value) === false) {
                return;
            }
            if ($this->isOptionValueExists($code, $value) === true) {
                return;
            }
            $this->addOption($code, $value);
        } catch (\Exception $e) {
            return;
        }
    }

    public function isValidValue(mixed $value): bool
    {
        if (strlen((string) $value) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Saves the options.
     */
    public function saveOptions(): void
    {
        $newValues = $this->getNewOptions();
        if (is_array($newValues) === false || count($newValues) === 0) {
            return;
        }
        foreach ($newValues as $attributeCode => $values) {
            $attribute = $this->getAttribute($attributeCode);
            foreach ($values as $label) {
                /**
                 * @var AttributeOptionLabelInterface $optionLabel
                 */
                $optionLabel = $this->labelFactory->create();
                $optionLabel->setStoreId(0);
                $optionLabel->setLabel($label);

                /**
                 * @var AttributeOptionInterface $option
                 */
                $option = $this->optionFactory->create();
                $option->setLabel($label);
                $option->setStoreLabels([$optionLabel]);
                $option->setSortOrder(0);
                $option->setIsDefault(false);

                try {
                    $this->attrOptionManagement->add(
                        Product::ENTITY,
                        $attribute->getAttributeId(),
                        $option
                    );
                    $this->log->logInfo(
                        sprintf('Created the option "%s" for the attribute "%s"', $label, $attributeCode)
                    );
                } catch (\Exception $e) {
                    $this->log->logError($e->getMessage());
                }
            }
        }
        $this->reset();
    }

    public function isOptionAttribute(mixed $code): bool
    {
        if (in_array($code, $this->ignoreAttributes) === true) {
            return false;
        }
        $attribute = $this->getAttribute($code);
        if (in_array($attribute->getFrontendInput(), $this->allowedInputs) &&
            $attribute->getBackendModel() == null
        ) {
            return true;
        }
        return false;
    }

    public function isOptionValueExists(mixed $code, mixed $value): bool
    {
        if (isset($this->attributeValues[$code]) === false) {
            $attribute = $this->getAttribute($code);
            $options = $attribute->getOptions();
            foreach ($options as $optionLabel) {
                $this->attributeValues[$code][] = $optionLabel->getLabel();
            }
        }
        if ((isset($this->attributeValues[$code]) && in_array($value, $this->attributeValues[$code]))
            || (isset($this->newValues[$code]) && in_array($value, $this->newValues[$code]))
        ) {
            return true;
        }
        return false;
    }

    public function addOption(mixed $code, mixed $value): void
    {
        $this->newValues[$code][] = $value;
    }

    /**
     * Clears the values that have been saved.
     */
    private function reset(): void
    {
        $this->newValues = [];
        $this->attributes = [];
        $this->attributeValues = [];
    }

    public function getNewOptions(): array
    {
        return $this->newValues;
    }

    private function getAttribute(mixed $code): \Magento\Catalog\Api\Data\ProductAttributeInterface
    {
        if (!isset($this->attributes[$code])) {
            $attribute = $this->attributeRepository->get($code);
            $this->attributes[$code] = $attribute;
        }
        return $this->attributes[$code];
    }
}
