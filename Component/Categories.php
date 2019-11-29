<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Exception;
use Symfony\Component\Yaml\Yaml;

class Categories extends ComponentAbstract
{
    protected $alias = 'categories';
    protected $name = 'Categories';
    protected $description = 'Component to import categories.';
    protected $groupFactory;
    protected $dirList;
    protected $category;
    private $mainAttributes = [
        'name',
        'is_active',
        'position',
        'include_in_menu',
        'description'
    ];

    public function __construct(
        \CtiDigital\Configurator\Api\LoggerInterface $log,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        Json $json,
        \Magento\Catalog\Model\CategoryFactory $category,
        \Magento\Store\Model\GroupFactory $groupFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $dirList
    ) {
        $this->category = $category;
        $this->groupFactory = $groupFactory;
        $this->dirList = $dirList;
        parent::__construct($log, $objectManager, $json);
    }

    public function processData($data = null)
    {
        if (isset($data['categories'])) {
            foreach ($data['categories'] as $store) {
                try {
                    $group = $this->getStoreGroup($store);
                    // Get the default category
                    $category = $this->getDefaultCategory($group);
                    if ($category === false) {
                        throw new ComponentException(
                            sprintf('No default category was found for the store group "%s"', $group)
                        );
                    }
                    if (isset($store['categories'])) {
                        $this->log->logInfo(sprintf('Updating categories for "%s"', $group));
                        $this->createOrUpdateCategory($category, $store['categories']);
                    }
                } catch (ComponentException $e) {
                    $this->log->logError($e->getMessage());
                }
            }
        }
    }

    /**
     * Gets the default category for the store group
     *
     * @param null $store
     * @return \Magento\Catalog\Model\Category|bool
     */
    public function getDefaultCategory($store = null)
    {
        $groupCollection = $this->groupFactory->create()->getCollection()
            ->addFieldToFilter('name', $store);
        if ($groupCollection->getSize() === 1) {
            /**
             * @var $group \Magento\Store\Model\Group
             */
            $group = $groupCollection->getFirstItem();
            $category = $this->category->create()->load($group->getRootCategoryId());
            return $category;
        }
        if ($groupCollection->getSize() > 1) {
            throw new ComponentException(
                sprintf('Multiple store groups were found with the name "%s"', $store)
            );
        }
        if ($groupCollection->getSize() === 0) {
            throw new ComponentException(
                sprintf('No store groups were found with the name "%s"', $store)
            );
        }
        return false;
    }

    /**
     * Creates/updates categories with the values in the YAML
     *
     * @param array $categories
     * @param \Magento\Catalog\Model\Category $parentCategory
     * @SuppressWarnings(PHPMD)
     */
    public function createOrUpdateCategory(
        \Magento\Catalog\Model\Category $parentCategory,
        $categories = array()
    ) {
        foreach ($categories as $categoryValues) {
            // Load the category using its name and parent category
            /**
             * @var $category \Magento\Catalog\Model\Category
             */
            $category = $this->category->create()->getCollection()
                ->addFieldToFilter('name', $categoryValues['name'])
                ->addFieldToFilter('parent_id', $parentCategory->getId())
                ->setPageSize(1)
                ->getFirstItem();

            foreach ($categoryValues as $attribute => $value) {
                switch ($attribute) {
                    case in_array($attribute, $this->mainAttributes):
                        $category->setData($attribute, $value);
                        break;
                    case 'category':
                        break;
                    case 'image':
                        $img = basename($value);
                        $path = parse_url($value);
                        $catMediaDir = $this->dirList->getPath('media') . '/' . 'catalog' . '/' . 'category' . '/';

                        if (!array_key_exists('host', $path)) {
                            $value = BP . '/' . trim($value, '/');
                        }

                        if (!@copy($value, $catMediaDir . $img)) {
                            $this->log->logError('Failed to find image: ' . $value, 1);
                            break;
                        }

                        $category->setImage($img);
                        break;
                    default:
                        $category->setCustomAttribute($attribute, $value);
                }
            }

            // Set the category to be active
            if (!(isset($categoryValues['is_active']))) {
                $category->setIsActive(true);
            }

            // Get the path. If the category exists, then append the '/' to the end
            $path = $parentCategory->getPath();
            if ($category->getId()) {
                $path = $path . '/';
            }
            $category->setAttributeSetId($category->getResource()->getEntityType()->getDefaultAttributeSetId());
            $category->setPath($path);
            $category->setParentId($parentCategory->getId());
            $category->save();

            $this->log->logInfo(
                sprintf('Updated category %s', $category->getName()),
                ($category->getLevel() - 1)
            );

            if (isset($categoryValues['categories'])) {
                $this->createOrUpdateCategory($category, $categoryValues['categories']);
            }
        }
    }

    /**
     * @param $data
     * @return string
     */
    private function getStoreGroup($data)
    {
        if (isset($data['store_group']) === true) {
            return $data['store_group'];
        }
        return 'Main Website Store';
    }
}
