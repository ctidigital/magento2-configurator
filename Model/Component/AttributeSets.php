<?php

namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Eav\Model\AttributeSetRepository;

/**
 * Class AttributeSets
 * @package CtiDigital\Configurator\Model\Component
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class AttributeSets extends YamlComponentAbstract
{
    protected $alias = 'attribute_sets';
    protected $name = 'Attribute Sets';
    protected $description = 'Component to create/maintain attribute sets.';

    /**
     * @var EavSetup
     */
    protected $eavSetup;

    /**
     * @var AttributeSetRepository
     */
    protected $attributeSetRepository;

    /**
     * AttributeSets constructor.
     * @param LoggingInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param EavSetup $eavSetup
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        EavSetupFactory $eavSetupFactory,
        AttributeSetRepositoryInterface $attributeSetRepository
    ) {

        parent::__construct($log, $objectManager);

        $this->eavSetup = $eavSetupFactory;
        $this->attributeSetRepository = $attributeSetRepository;
    }

    /**
     * @param array $attributeConfigurationData
     */
    protected function processData($attributeConfigurationData = null)
    {
        try {
            foreach ($attributeConfigurationData['attribute_sets'] as $attributeSetConfiguration) {
                $this->processAttributeSet($attributeSetConfiguration);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * @param array $attributeSetConfig
     */
    protected function processAttributeSet(array $attributeSetConfig)
    {
        $this->eavSetup->addAttributeSet(Product::ENTITY, $attributeSetConfig['name']);

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, $attributeSetConfig['name']);
        $attributeSetEntity = $this->attributeSetRepository->get($attributeSetId);
        if (array_key_exists('inherit', $attributeSetConfig)) {
            $attributeSetEntity->initFromSkeleton($this->getAttributeSetId($attributeSetConfig['inherit']));
            $this->attributeSetRepository->save($attributeSetEntity);
        }

        if (array_key_exists('groups', $attributeSetConfig) && count($attributeSetConfig['groups']) > 0) {
            $this->addAttributeGroups($attributeSetEntity, $attributeSetConfig['groups']);
            $this->addAttributeGroupAssociations($attributeSetEntity, $attributeSetConfig['groups']);
        }
    }

    /**
     * @param AttributeSetInterface $attributeSetEntity
     * @param array $attributeGroupData
     */
    protected function addAttributeGroups(AttributeSetInterface $attributeSetEntity, array $attributeGroupData)
    {
        /*        if ($attributeSetEntity->getDefaultGroupId()) {
                    $this->eavSetup->removeAttributeGroup(
                        Product::ENTITY,
                        $attributeSetEntity->getId(),
                        $attributeSetEntity->getDefaultGroupId()
                    );
                }*/

        foreach ($attributeGroupData as $group) {
            $attributeSetName = $attributeSetEntity->getAttributeSetName();
            $this->eavSetup->addAttributeGroup(Product::ENTITY, $attributeSetName, $group['name']);
        }
    }

    /**
     * @param AttributeSetInterface $attributeSetEntity
     * @param array $attributeGroupData
     */
    protected function addAttributeGroupAssociations(
        AttributeSetInterface $attributeSetEntity,
        array $attributeGroupData
    ) {
        foreach ($attributeGroupData as $group) {
            foreach ($group['attributes'] as $attributeCode) {
                $attributeData = $this->eavSetup->getAttribute(Product::ENTITY, $attributeCode);

                if (count($attributeData) === 0) {
                    throw new ComponentException("Attribute '{$attributeCode}' does not exist.");
                }

                $this->eavSetup->addAttributeToGroup(
                    Product::ENTITY,
                    $attributeSetEntity->getId(),
                    $group['name'],
                    $attributeCode
                );
            }
        }
    }

    /**
     * @param $attributeSetName
     * @return string
     */
    protected function getAttributeSetId($attributeSetName)
    {
        $attributeSetData = $this->eavSetup->getAttributeSet(Product::ENTITY, $attributeSetName);
        if (array_key_exists('attribute_set_id', $attributeSetData)) {
            return $attributeSetData['attribute_set_id'];
        }

        throw new ComponentException('Could not find attribute set name.');
    }
}
