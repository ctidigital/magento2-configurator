<?php
namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\ObjectManagerInterface;
use FireGento\FastSimpleImport\Model\ImporterFactory;
use Magento\ImportExport\Model\Import;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\GroupManagementInterface;

class Customers extends CsvComponentAbstract
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

    protected $requiredAddressFields = [
        '_address_firstname',
        '_address_lastname',
        '_address_street',
        '_address_city',
        '_address_country_id',
        '_address_telephone'
    ];

    /**
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $indexerFactory;

    /**
     * @var \Magento\Framework\Validator\EmailAddress
     */
    protected $emailValidator;

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

    /**
     * @var boolean
     */
    protected $customerHasAddress = false;

    protected $emailAddresses = [];

    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        ImporterFactory $importerFactory,
        GroupRepositoryInterface $groupRepository,
        GroupManagementInterface $groupManagement,
        SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Framework\Validator\EmailAddress $emailValidator
    ) {
        $this->importerFactory = $importerFactory;
        $this->groupRepository = $groupRepository;
        $this->groupManagement = $groupManagement;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->indexerFactory = $indexerFactory;
        $this->emailValidator = $emailValidator;
        parent::__construct($log, $objectManager);
    }

    /**
     * @param null $data
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function processData($data = null)
    {
        $this->getColumnHeaders($data);
        unset($data[0]);

        $customerImport = [];

        $rowIndex = 0;
        foreach ($data as $customer) {
            $row = [];
            $extraItem = false;
            $skipCustomer = false;
            $this->setCustomerHasAddress(false);
            foreach ($this->getHeaders() as $key => $columnHeader) {
                if ($skipCustomer === true) {
                    continue;
                }
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

                if ($this->getCustomerHasAddress() === false &&
                    $customer[$key] !== '' &&
                    $this->getIsAddressColumn($columnHeader)) {
                    $this->setCustomerHasAddress(true);
                }

                $row[$columnHeader] = $customer[$key];

                if ($columnHeader === self::CUSTOMER_EMAIL_HEADER) {
                    $emailAddress = trim(strtolower($row[self::CUSTOMER_EMAIL_HEADER]));
                    $row[self::CUSTOMER_EMAIL_HEADER] = $emailAddress;
                    if (strlen($emailAddress) === 0) {
                        // If no email address is specified then it's an extra address being specified.
                        $extraItem = true;
                    }
                    if (strlen($emailAddress) > 0) {
                        if ($this->emailValidator->isValid($emailAddress) === false) {
                            $this->log->logError(
                                sprintf(
                                    'The email address %s is not valid. Skipping row %s',
                                    $emailAddress,
                                    $rowIndex
                                )
                            );
                            $skipCustomer = true;
                            continue;
                        }
                        if (array_key_exists($emailAddress, $this->emailAddresses)) {
                            $this->log->logError(
                                sprintf(
                                    'The email address %s has already been used. Skipping row %s',
                                    $row[self::CUSTOMER_EMAIL_HEADER],
                                    $rowIndex
                                )
                            );
                            $skipCustomer = true;
                            continue;
                        }
                        $this->emailAddresses[$emailAddress] = true;
                    }
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

                if ($extraItem === false && ($columnHeader === 'firstname' || $columnHeader === 'lastname')) {
                    if (strlen($row[$columnHeader]) === 0) {
                        $this->log->logError(
                            sprintf(
                                'The column "%s" must have a value for row "%s". This row will be skipped.',
                                $columnHeader,
                                $rowIndex
                            )
                        );
                        $skipCustomer = true;
                        continue;
                    }
                }
            }

            if ($this->getCustomerHasAddress() === true && $this->isAddressValid($row) === false) {
                $this->log->logInfo(
                    sprintf(
                        'The address for row %s is not valid and will not be imported (the customer will be though)',
                        $rowIndex
                    )
                );
                $row = $this->removeAddressFields($row);
            }
            if ($skipCustomer === false) {
                $customerImport[] = $row;
            }
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
     * @param $column
     * @return bool
     */
    public function getIsAddressColumn($column)
    {
        if (substr($column, 0, 9) === '_address_') {
            return true;
        }
        return false;
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

    /**
     * @param boolean $hasAddress
     */
    public function setCustomerHasAddress($hasAddress)
    {
        $this->customerHasAddress = $hasAddress;
    }

    /**
     * @return bool
     */
    public function getCustomerHasAddress()
    {
        return $this->customerHasAddress;
    }

    public function isAddressValid($customer)
    {
        foreach ($this->requiredAddressFields as $required) {
            if (!in_array($required, array_keys($customer)) || strlen($customer[$required]) === 0 || $customer[$required] == '0') {
                return false;
            }
        }
        return true;
    }

    public function removeAddressFields($customer) {
        foreach ($customer as $column => $value) {
            if ($this->getIsAddressColumn($column)) {
                unset($customer[$column]);
            }
        }
        return $customer;
    }

    private function reindex()
    {
        $this->log->logInfo('Reindexing the customer grid');
        $customerGrid = $this->indexerFactory->create();
        $customerGrid->load('customer_grid');
        $customerGrid->reindexAll();
    }
}
