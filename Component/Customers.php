<?php
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
    const CUSTOMER_EMAIL_HEADER = 'email';
    const CUSTOMER_GROUP_HEADER = 'group_id';

    protected $alias = 'customers';
    protected $name = 'Customers';
    protected $description = 'Import customers and addresses';

    protected $requiredColumns = [
        'email',
        '_website',
        '_store',
    ];

    /**
     * @var ImporterFactory
     */
    protected $importerFactory;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var GroupManagementInterface
     */
    protected $groupManagement;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $criteriaBuilder;

    /**
     * @var IndexerFactory
     */
    protected $indexerFactory;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var array
     */
    protected $customerGroups;

    /**
     * @var int
     */
    protected $groupDefault;

    /**
     * @var array
     */
    protected $columnHeaders = [];

    public function __construct(
        ImporterFactory $importerFactory,
        GroupRepositoryInterface $groupRepository,
        GroupManagementInterface $groupManagement,
        SearchCriteriaBuilder $criteriaBuilder,
        IndexerFactory $indexerFactory,
        LoggerInterface $log
    ) {
        $this->importerFactory = $importerFactory;
        $this->groupRepository = $groupRepository;
        $this->groupManagement = $groupManagement;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->indexerFactory = $indexerFactory;
        $this->log = $log;
    }

    /**
     * @param null $data
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute($data = null)
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
                    strlen($row[self::CUSTOMER_EMAIL_HEADER]) === 0) {
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
     * Check the headers have been set correctly
     *
     * @param $data
     *
     * @return void
     */
    public function getColumnHeaders($data)
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

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->columnHeaders;
    }

    /**
     * Check if the group is valid
     *
     * @param $group
     *
     * @return bool
     */
    public function isValidGroup($group)
    {
        if (strlen($group) === 0) {
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

    public function getDefaultGroupId()
    {
        if ($this->groupDefault === null) {
            $this->groupDefault = $this->groupManagement->getDefaultGroup()->getId();
        }
        return $this->groupDefault;
    }

    private function reindex()
    {
        $this->log->logInfo('Reindexing the customer grid');
        $customerGrid = $this->indexerFactory->create();
        $customerGrid->load('customer_grid');
        $customerGrid->reindexAll();
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
