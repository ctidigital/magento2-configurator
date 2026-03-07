<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Customer\Model\Customer;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\LocalizedException;
use Magento\Eav\Model\AttributeRepository;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Model\ResourceModel\Attribute;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class CustomerAttributes extends Attributes
{
    private const DEFAULT_ATTRIBUTE_SET_ID = 1;
    private const DEFAULT_ATTRIBUTE_GROUP_ID = 1;

    protected string $alias = 'customer_attributes';
    protected string $name = 'Customer Attributes';
    protected string $description = 'Component to create/maintain customer attributes.';

    protected string $entityTypeId = Customer::ENTITY;

    /**
     * @var array
     */
    protected array $customerConfigMap = [
        'visible' => 'is_visible',
        'position' => 'sort_order',
        'system' => 'is_system'
    ];

    protected array $defaultForms = [
        'values' => [
            'customer_account_create',
            'customer_account_edit',
            'adminhtml_checkout',
            'adminhtml_customer'
        ]
    ];

    public function __construct(
        EavSetup $eavSetup,
        AttributeRepository $attributeRepository,
        protected readonly CustomerSetupFactory $customerSetup,
        protected readonly Attribute $attributeResource,
        private readonly LoggerInterface $log,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Eav\Model\Config $eavConfig
    ) {
        parent::__construct($eavSetup, $attributeRepository, $log, $attrOptionCollectionFactory, $eavConfig);
        $this->attributeConfigMap = array_merge($this->attributeConfigMap, $this->customerConfigMap);
    }

    public function execute(mixed $data = null): void
    {
        try {
            foreach ($data['customer_attributes'] as $attributeCode => $attributeConfiguration) {
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
     */
    protected function addAdditionalValues(mixed $attributeCode, array $attributeConfiguration): void
    {
        if ($this->attributeExists) {
            return;
        }
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

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
