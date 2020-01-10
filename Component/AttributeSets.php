<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Model\AttributeSetRepository;

/**
 * Class AttributeSets
 * @package CtiDigital\Configurator\Model\Component
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class AttributeSets implements ComponentInterface
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
     * @var LoggerInterface
     */
    private $log;

    /**
     * AttributeSets constructor.
     * @param LoggerInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param EavSetup $eavSetup
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        EavSetup $eavSetup,
        AttributeSetRepositoryInterface $attributeSetRepository,
        LoggerInterface $log
    ) {
        $this->eavSetup = $eavSetup;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->log = $log;
    }

    /**
     * @param array $attributeConfigurationData
     */
    public function execute($attributeConfigurationData = null)
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

        $this->log->logInfo(sprintf('Creating attribute set: "%s"', $attributeSetConfig['name']));

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, $attributeSetConfig['name']);
        $attributeSetEntity = $this->attributeSetRepository->get($attributeSetId);
        if (array_key_exists('inherit', $attributeSetConfig)) {
            $attributeSetEntity->initFromSkeleton($this->getAttributeSetId($attributeSetConfig['inherit']));
            $this->attributeSetRepository->save($attributeSetEntity);
        }

        if (array_key_exists('groups', $attributeSetConfig) && count($attributeSetConfig['groups']) > 0) {
            $this->addAttributeGroups($attributeSetEntity, $attributeSetConfig['groups']);
        }
    }

    /**
     * @param AttributeSetInterface $attributeSetEntity
     * @param array $attributeGroupData
     */
    protected function addAttributeGroups(AttributeSetInterface $attributeSetEntity, array $attributeGroupData)
    {
        $attributeSetName = $attributeSetEntity->getAttributeSetName();

        // Loop through the groups that belong to the attribute set
        foreach ($attributeGroupData as $group) {
            try {
                // Used to predetermine the code if not using a custom attribute group code
                if (!isset($group['code'])) {
                    $group['code'] = $this->eavSetup->convertToAttributeGroupCode($group['name']);
                }

                // Check if the attribute group exist
                $attributeGroup = $this->eavSetup->getAttributeGroup(
                    Product::ENTITY,
                    $attributeSetName,
                    $group['code'],
                    'attribute_set_id'
                );

                // If not then create the group
                if (!$attributeGroup) {
                    $this->eavSetup->addAttributeGroup(Product::ENTITY, $attributeSetName, $group['name']);
                    $this->log->logInfo(sprintf('Creating group: "%s"', $group['name']), 1);
                }

                if ($attributeGroup) {
                    $this->log->logComment(sprintf('Existing group: "%s"', $group['name']), 1);
                }

                // Attempt to associate the attributes to the group
                $this->addAttributeGroupAssociations($attributeSetEntity, $group);
            } catch (\Zend_Db_Statement_Exception $exception) {
                $this->log->logError(
                    'Magento sometimes uses different attribute codes to attribute names. '
                    .'You may require to specify the code too.',
                    1
                );
                $this->log->logError(
                    sprintf('Attribute Set: %s, Group: %s', $attributeSetName, $group['name']),
                    1
                );
                $this->log->logError($exception->getMessage(), 1);
            }
        }
    }

    /**
     * @param AttributeSetInterface $attributeSetEntity
     * @param array $group
     */
    protected function addAttributeGroupAssociations(
        AttributeSetInterface $attributeSetEntity,
        array $group
    ) {
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

            $this->log->logInfo(sprintf('Adding attribute "%s"', $attributeCode), 2);
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
}
