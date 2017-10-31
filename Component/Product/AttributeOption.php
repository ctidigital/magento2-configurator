<?php
namespace CtiDigital\Configurator\Component\Product;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Catalog\Model\Product;
use CtiDigital\Configurator\Model\LoggerInterface;

class AttributeOption
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var AttributeOptionManagementInterface
     */
    protected $attrOptionManagement;

    /**
     * @var AttributeOptionLabelInterfaceFactory
     */
    protected $labelFactory;

    /**
     * @var AttributeOptionInterfaceFactory
     */
    protected $optionFactory;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var []
     */
    private $attributes = [];

    /**
     * @var []
     */
    private $attributeValues = [];

    /**
     * @var []
     */
    private $allowedInputs = ['select', 'multiselect'];

    /**
     * @var array
     */
    private $ignoreAttributes = [
        'visibility',
        'tax_class_id'
    ];

    /**
     * @var []
     */
    private $newValues = [];

    /**
     * AttributeOption constructor.
     *
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param AttributeOptionManagementInterface $attrOptionManagement
     * @param AttributeOptionLabelInterfaceFactory $labelFactory
     * @param AttributeOptionInterfaceFactory $optionFactory
     * @param LoggerInterface $log
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        AttributeOptionManagementInterface $attrOptionManagement,
        AttributeOptionLabelInterfaceFactory $labelFactory,
        AttributeOptionInterfaceFactory $optionFactory,
        LoggerInterface $log
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->attrOptionManagement = $attrOptionManagement;
        $this->labelFactory = $labelFactory;
        $this->optionFactory = $optionFactory;
        $this->log = $log;
    }

    /**
     * @param $code
     * @param $value
     */
    public function processAttributeValues($code, $value)
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

    /**
     * @param $value
     *
     * @return bool
     */
    public function isValidValue($value)
    {
        if (strlen($value) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Saves the options
     */
    public function saveOptions()
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
                $option->setLabel($optionLabel);
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

    /**
     * @param $code
     *
     * @return bool
     */
    public function isOptionAttribute($code)
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

    /**
     * @param $code
     * @param $value
     *
     * @return bool
     */
    public function isOptionValueExists($code, $value)
    {
        if (isset($this->attributeValues[$code]) === false) {
            $attribute = $this->getAttribute($code);
            $options = $attribute->getOptions();
            foreach ($options as $optionLabel) {
                $this->attributeValues[$code][] = $optionLabel->getLabel();
            }
        }
        if (
            (isset($this->attributeValues[$code]) && in_array($value, $this->attributeValues[$code]))
            || (isset($this->newValues[$code]) && in_array($value, $this->newValues[$code]))
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param $code
     * @param $value
     */
    public function addOption($code, $value)
    {
        $this->newValues[$code][] = $value;
    }

    /**
     * Clears the values that have been saved
     */
    private function reset()
    {
        $this->newValues = [];
        $this->attributes = [];
        $this->attributeValues = [];
    }

    /**
     * @return mixed
     */
    public function getNewOptions()
    {
        return $this->newValues;
    }

    /**
     * @param $code
     *
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    private function getAttribute($code)
    {
        if (!isset($this->attributes[$code])) {
            $attribute = $this->attributeRepository->get($code);
            $this->attributes[$code] = $attribute;
        }
        return $this->attributes[$code];
    }
}
