<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\GroupFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class Categories implements ComponentInterface
{
    protected $alias = 'categories';
    protected $name = 'Categories';
    protected $description = 'Component to import categories.';

    private $mainAttributes = [
        'name',
        'is_active',
        'position',
        'include_in_menu',
        'description'
    ];

    /**
     * Categories constructor.
     * @param LoggerInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param CategoryFactory $category
     * @param GroupFactory $groupFactory
     * @param DirectoryList $dirList
     * @param BlockInterfaceFactory $blockFactory
     */
    public function __construct(
        protected readonly LoggerInterface $log,
        protected readonly ObjectManagerInterface $objectManager,
        protected readonly CategoryFactory $category,
        protected readonly GroupFactory $groupFactory,
        protected readonly DirectoryList $dirList,
        protected readonly BlockInterfaceFactory $blockFactory
    ) {}

    public function execute($data = null)
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
     * @return Category|bool
     */
    public function getDefaultCategory($store = null)
    {
        $groupCollection = $this->groupFactory->create()->getCollection()
            ->addFieldToFilter('name', $store);
        if ($groupCollection->getSize() === 1) {
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
     * @param Category $parentCategory
     * @SuppressWarnings(PHPMD)
     * @throws FileSystemException
     */
    public function createOrUpdateCategory(
        Category $parentCategory,
        $categories = []
    ) {
        foreach ($categories as $categoryValues) {
            // Load the category using its name and parent category
            /**
             * @var $category Category
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
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        $img = basename((string) $value);
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        $path = parse_url((string) $value);
                        $catMediaDir = $this->dirList->getPath('media') . '/' . 'catalog' . '/' . 'category' . '/';

                        if (!array_key_exists('host', $path)) {
                            $value = BP . '/' . trim((string) $value, '/');
                        }

                        // phpcs:ignore
                        if (!@copy($value, $catMediaDir . $img)) {
                            $this->log->logError('Failed to find image: ' . $value, 1);
                            break;
                        }

                        $category->setImage($img);
                        break;
                    // Attaching cms block
                    case 'cms_block':
                        // getting block by name
                        $block = $this->blockFactory->create()->getCollection()
                            ->addFieldToFilter('title', $value)
                            ->setPageSize(1)
                            ->getFirstItem();

                        // check if block exist
                        if (!$block) {
                            $this->log->logError("Can't find cms block with name '%s'", $value);
                        }

                        // Attach cms block by id
                        $category->setData('landing_page', $block->getId());
                        // set category display mode to Satic block and products
                        $category->setData('display_mode', 'PRODUCTS_AND_PAGE');

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
