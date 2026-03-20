<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Model\CmsBlockResolver;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\GroupFactory;
use Magento\Framework\App\Filesystem\DirectoryList;

class Categories implements ComponentInterface
{
    private const CATEGORY_MEDIA_DB_PATH = '/media/catalog/category/';
    private const CATEGORY_IMAGE_BACKEND_MODEL = 'Magento\Catalog\Model\Category\Attribute\Backend\Image';

    protected string $alias = 'categories';
    protected string $name = 'Categories';
    protected string $description = 'Component to import categories.';

    private array $mainAttributes = [
        'name',
        'is_active',
        'position',
        'include_in_menu',
        'description'
    ];

    public function __construct(
        protected readonly LoggerInterface $log,
        protected readonly ObjectManagerInterface $objectManager,
        protected readonly CategoryFactory $category,
        protected readonly GroupFactory $groupFactory,
        protected readonly DirectoryList $dirList,
        protected readonly BlockInterfaceFactory $blockFactory,
        private readonly DriverInterface $driver,
        private readonly CmsBlockResolver $cmsBlockResolver
    ) {
    }

    /**
     * Execute the component with the given category data.
     */
    public function execute(mixed $data = null): void
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
                } catch (ComponentException $exception) {
                    $this->log->logError($exception->getMessage());
                }
            }
        }
    }

    /**
     * Gets the default category for the store group.
     */
    public function getDefaultCategory(mixed $store = null): Category|bool
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
     * Creates/updates categories with the values in the YAML.
     *
     * @param Category $parentCategory
     * @param array $categories
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @throws FileSystemException
     */
    public function createOrUpdateCategory(
        Category $parentCategory,
        array $categories = []
    ): void {
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
                if (in_array($attribute, $this->mainAttributes, true)) {
                    $category->setData($attribute, $value);
                    continue;
                }

                if ($attribute === 'category') {
                    continue;
                }

                if ($this->isCategoryImageAttribute($category, (string) $attribute)) {
                    try {
                        $category->setData($attribute, $this->copyCategoryImage((string) $value));
                    } catch (FileSystemException $e) {
                        $this->log->logError('Failed to copy image "' . $value . '": ' . $e->getMessage(), 1);
                    }
                    continue;
                }

                if ($attribute === 'landing_page') {
                    $category->setData(
                        'landing_page',
                        $this->cmsBlockResolver->resolve($value, (int) $category->getStoreId())
                    );
                    continue;
                }

                if ($attribute === 'cms_block') {
                    // Look up by CMS block title (legacy; prefer 'landing_page' with block identifier)
                    $block = $this->blockFactory->create()->getCollection()
                        ->addFieldToFilter('title', $value)
                        ->setPageSize(1)
                        ->getFirstItem();

                    if (!$block->getId()) {
                        $this->log->logError(sprintf("Can't find cms block with title '%s'", $value));
                        continue;
                    }

                    // Attach cms block by id
                    $category->setData('landing_page', $block->getId());
                    // Set category display mode to static block and products
                    $category->setData('display_mode', 'PRODUCTS_AND_PAGE');

                    continue;
                }

                $category->setCustomAttribute($attribute, $value);
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
            $category->setStoreId(0);
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
     * Copy an imported category image into pub/media/catalog/category and return the DB value.
     *
     * @throws FileSystemException
     */
    private function copyCategoryImage(string $value): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $img = basename($value);
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $path = parse_url($value);
        $catMediaDir = $this->dirList->getPath('media') . '/catalog/category/';

        if (!is_array($path) || !array_key_exists('host', $path)) {
            $value = BP . '/' . trim($value, '/');
        }

        $this->driver->createDirectory($catMediaDir);
        $this->driver->copy($value, $catMediaDir . $img);

        return self::CATEGORY_MEDIA_DB_PATH . $img;
    }

    /**
     * Returns true when the given attribute should be handled as a category image:
     * either the core 'image' attribute or any attribute using Magento's category image
     * frontend input / backend model.
     */
    private function isCategoryImageAttribute(Category $category, string $attribute): bool
    {
        if ($attribute === 'image') {
            return true;
        }

        $attributeModel = $category->getResource()->getAttribute($attribute);
        if (!$attributeModel) {
            return false;
        }

        return $attributeModel->getFrontendInput() === 'image'
            || $attributeModel->getBackendModel() === self::CATEGORY_IMAGE_BACKEND_MODEL;
    }

    /**
     * Extract the store group name from the data array.
     */
    private function getStoreGroup(array $data): string
    {
        if (isset($data['store_group']) === true) {
            return $data['store_group'];
        }
        return 'Main Website Store';
    }

    /**
     * Return the component alias.
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Return the component description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
