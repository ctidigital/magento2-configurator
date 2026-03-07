<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use FireGento\FastSimpleImport\Model\ImporterFactory;
use Magento\ImportExport\Model\Import;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Indexer\Model\IndexerFactory;

class Customers implements ComponentInterface
{
    private const CUSTOMER_EMAIL_HEADER = 'email';
    private const CUSTOMER_GROUP_HEADER = 'group_id';

    protected string $alias = 'customers';
    protected string $name = 'Customers';
    protected string $description = 'Import customers and addresses';

    /**
     * @var array
     */
    protected array $requiredColumns = [
        'email',
        '_website',
        '_store',
    ];

    protected ?array $customerGroups = null;

    protected mixed $groupDefault = null;

    protected array $columnHeaders = [];

    public function __construct(
        protected readonly ImporterFactory $importerFactory,
        protected readonly GroupRepositoryInterface $groupRepository,
        protected readonly GroupManagementInterface $groupManagement,
        protected readonly SearchCriteriaBuilder $criteriaBuilder,
        protected readonly IndexerFactory $indexerFactory,
        private readonly LoggerInterface $log
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(mixed $data = null): void
    {
        $this->getColumnHeaders($data);
        unset($data[0]);

        $customerImport = [];

        $rowIndex = 0;
        foreach ($data as $customer) {
            $row = [];
            $extraItem = false;
            foreach ($this->getHeaders() as $key => $columnHeader) {
                if (!array_key_exists($key, $customer)) {
                    $this->log->logError(
                        sprintf(
                            'The key "%s" was not found on row "%s".',
                            $key,
                            $rowIndex
                        )
                    );
                    continue;
                }
                $row[$columnHeader] = $customer[$key];

                if ($columnHeader === self::CUSTOMER_EMAIL_HEADER &&
                    strlen((string) $row[self::CUSTOMER_EMAIL_HEADER]) === 0) {
                    // If no email address is specified then it's an extra address being specified.
                    $extraItem = true;
                }

                if ($extraItem === false &&
                    $columnHeader === self::CUSTOMER_GROUP_HEADER &&
                    $this->isValidGroup($row[$columnHeader]) === false
                ) {
                    $this->log->logError(
                        sprintf(
                            'The customer group ID "%s" is not valid for row "%s". Default value set.',
                            $row[$columnHeader],
                            $rowIndex
                        )
                    );
                    $row[self::CUSTOMER_GROUP_HEADER] = $this->getDefaultGroupId();
                }
            }
            $customerImport[] = $row;
            $rowIndex++;
        }

        try {
            /**
             * @var $importer \FireGento\FastSimpleImport\Model\Importer
             */
            $importer = $this->importerFactory->create();
            $importer->setEntityCode('customer_composite');
            $importer->setBehavior(Import::BEHAVIOR_APPEND);
            $importer->processImport($customerImport);
            $this->reindex();
        } catch (\Exception $e) {
            $this->log->logError($e->getMessage());
        }
        $this->log->logInfo($importer->getLogTrace());
        $this->log->logInfo($importer->getErrorMessages());
    }

    /**
     * Check the headers have been set correctly.
     */
    public function getColumnHeaders(mixed $data): void
    {
        if (!isset($data[0])) {
            throw new ComponentException('No data has been found in the import file');
        }
        foreach ($data[0] as $heading) {
            $this->columnHeaders[] = $heading;
        }
        foreach ($this->requiredColumns as $column) {
            if (!in_array($column, $this->columnHeaders)) {
                throw new ComponentException(sprintf('The column "%s" is required.', $column));
            }
        }
    }

    public function getHeaders(): array
    {
        return $this->columnHeaders;
    }

    /**
     * Check if the group is valid.
     */
    public function isValidGroup(mixed $group): bool
    {
        if (strlen((string) $group) === 0) {
            return false;
        }
        if ($this->customerGroups === null) {
            $groups = $this->groupRepository->getList($this->criteriaBuilder->create());
            foreach ($groups->getItems() as $customerGroup) {
                $this->customerGroups[] = $customerGroup->getId();
            }
        }
        if (in_array($group, $this->customerGroups)) {
            return true;
        }
        return false;
    }

    public function getDefaultGroupId(): mixed
    {
        if ($this->groupDefault === null) {
            $this->groupDefault = $this->groupManagement->getDefaultGroup()->getId();
        }
        return $this->groupDefault;
    }

    private function reindex(): void
    {
        $this->log->logInfo('Reindexing the customer grid');
        $customerGrid = $this->indexerFactory->create();
        $customerGrid->load('customer_grid');
        $customerGrid->reindexAll();
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
