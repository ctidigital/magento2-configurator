<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\Product\Attribute\Repository as ProductAttributeRepository;
use Magento\Eav\Model\AttributeRepository;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Model\ResourceModel\Attribute;

/**
 * Class CustomerAttributes
 * @package CtiDigital\Configurator\Model\Component
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class CustomerAttributes extends Attributes
{
    const DEFAULT_ATTRIBUTE_SET_ID = 1;
    const DEFAULT_ATTRIBUTE_GROUP_ID = 1;

    protected $alias = 'customer_attributes';
    protected $name = 'Customer Attributes';
    protected $description = 'Component to create/maintain customer attributes.';

    /**
     * @var string
     */
    protected $entityTypeId = Customer::ENTITY;

    protected $customerConfigMap = [
        'visible' => 'is_visible',
        'position' => 'sort_order'
    ];

    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetup;

    /**
     * @var Attribute
     */
    protected $attributeResource;

    /**
     * @var array
     */
    protected $defaultForms = [
        'values' => [
            'customer_account_create',
            'customer_account_edit',
            'adminhtml_checkout',
            'adminhtml_customer'
        ]
    ];

    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        EavSetup $eavSetup,
        ProductAttributeRepository $productAttributeRepository,
        AttributeRepository $attributeRepository,
        CustomerSetupFactory $customerSetupFactory,
        Attribute $attributeResource
    ) {
        $this->attributeConfigMap = array_merge($this->attributeConfigMap, $this->customerConfigMap);
        $this->customerSetup = $customerSetupFactory;
        $this->attributeResource = $attributeResource;
        parent::__construct($log, $objectManager, $eavSetup, $productAttributeRepository);
    }

    /**
     * @param array $attributeConfigurationData
     */
    protected function processData($attributeConfigurationData = null)
    {
        try {
            foreach ($attributeConfigurationData['customer_attributes'] as $attributeCode => $attributeConfiguration) {
                $this->processAttribute($attributeCode, $attributeConfiguration);
                $this->addAdditionalValues($attributeCode, $attributeConfiguration);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * Adds necessary additional values to the attribute. Without these, values can't be saved
     * to the attribute and it won't appear in any forms.
     *
     * @param $attributeCode
     * @param $attributeConfiguration
     */
    protected function addAdditionalValues($attributeCode, $attributeConfiguration)
    {
        if (!isset($attributeConfiguration['used_in_forms']) ||
            !isset($attributeConfiguration['used_in_forms']['values'])) {
            $attributeConfiguration['used_in_forms'] = $this->defaultForms;
        }

        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetup->create();
        try {
            $attribute = $customerSetup->getEavConfig()
                ->getAttribute($this->entityTypeId, $attributeCode)
                ->addData([
                    'attribute_set_id' => self::DEFAULT_ATTRIBUTE_SET_ID,
                    'attribute_group_id' => self::DEFAULT_ATTRIBUTE_GROUP_ID,
                    'used_in_forms' => $attributeConfiguration['used_in_forms']['values']
                ]);
            $this->attributeResource->save($attribute);
        } catch (LocalizedException $e) {
            $this->log->logError(sprintf(
                'Error applying additional values to %s: %s',
                $attributeCode,
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            $this->log->logError(sprintf(
                'Error saving additional values for %s: %s',
                $attributeCode,
                $e->getMessage()
            ));
        }
    }
}
