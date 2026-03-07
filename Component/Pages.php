<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;

class Pages implements ComponentInterface
{
    protected string $alias = 'pages';
    protected string $name = 'Pages';
    protected string $description = 'Component to create/maintain pages.';

    protected array $requiredFields = ['title'];

    protected array $defaultValues = ['page_layout' => 'empty', 'is_active' => '1'];

    public function __construct(
        protected readonly PageRepositoryInterface $pageRepository,
        protected readonly PageInterfaceFactory $pageFactory,
        private readonly StoreRepositoryInterface $storeRepository,
        private readonly LoggerInterface $log
    ) {
    }

    /**
     * Loop through the data array and process page data.
     */
    public function execute(mixed $data = null): void
    {
        try {
            foreach ($data as $identifier => $data) {
                $this->processPage($identifier, $data);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * Create or update page data.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function processPage(mixed $identifier, mixed $data): void
    {

        try {
            foreach ($data['page'] as $pageData) {
                if (isset($pageData['stores'])) {
                    foreach ($pageData['stores'] as $storeCode) {
                        $store = $this->storeRepository->get($storeCode);
                        $pageId = $this->pageFactory->create()->checkIdentifier($identifier, $store->getId());
                    }
                } else {
                    $pageId = $this->pageFactory->create()->checkIdentifier($identifier, 0);
                }

                /** @var \Magento\Cms\Api\Data\PageInterface $page */
                if ($pageId) {
                    $page = $this->pageRepository->getById($pageId);
                } else {
                    $page = $this->pageFactory->create();
                    $page->setIdentifier($identifier);
                }

                $this->checkRequiredFields($pageData);
                $this->setDefaultFields($pageData);

                // Loop through each attribute of the data array
                foreach ($pageData as $key => $value) {
                    // Check if content is from a file source
                    if ($key == "source") {
                        $key = 'content';
                        // phpcs:ignore Magento2.Functions.DiscouragedFunction
                        $value = file_get_contents(BP . '/' . $value);
                    }

                    // Skip stores
                    if ($key == "stores") {
                        continue;
                    }

                    // Log the old value if any
                    $this->log->logComment(sprintf(
                        "Checking page %s, key %s => %s",
                        $identifier . ' (' . $page->getId() . ')',
                        $key,
                        $page->getData($key)
                    ), 1);

                    // Check if there is a difference in value
                    if ($page->getData($key) != $value) {
                        $page->setData($key, $value);

                        $this->log->logInfo(sprintf(
                            "Set page %s, key %s => %s",
                            $identifier . ' (' . $page->getId() . ')',
                            $key,
                            $value
                        ), 1);
                    }
                }

                // Process stores
                $page->setStores([0]);
                if (isset($pageData['stores'])) {
                    $page->unsetData('store_id');
                    $page->unsetData('store_data');

                    $stores = [];
                    foreach ($pageData['stores'] as $code) {
                        $stores[] = $store = $this->storeRepository->get($code)->getId();
                    }

                    $page->setStores($stores);
                }

                //we only need to save if the model has changed
                if ($page->hasDataChanges()) {
                    $this->pageRepository->save($page);
                    $this->log->logInfo(sprintf(
                        "Save page %s",
                        $identifier . ' (' . $page->getId() . ')'
                    ));
                }
            }
        } catch (NoSuchEntityException $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * Check the required fields are set.
     *
     * @throws ComponentException
     */
    protected function checkRequiredFields(array $pageData): void
    {
        foreach ($this->requiredFields as $key) {
            if (!array_key_exists($key, $pageData)) {
                throw new ComponentException('Required Data Missing ' . $key);
            }
        }
    }

    /**
     * Add default page data if fields not set.
     */
    protected function setDefaultFields(array &$pageData): void
    {
        foreach ($this->defaultValues as $key => $value) {
            if (!array_key_exists($key, $pageData)) {
                $pageData[$key] = $value;
            }
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
