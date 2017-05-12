<?php
namespace CtiDigital\Configurator\Model\Component;

use Symfony\Component\Yaml\Yaml;
use Magento\Framework\ObjectManagerInterface;
use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\TablerateFactory;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate;
use Magento\Store\Model\WebsiteFactory;
use Magento\Store\Model\Website;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\Region;

class ShippingTableRates extends YamlComponentAbstract
{
    protected $alias = "shippingtablerates";
    protected $name = "Shipping Table Rates";
    protected $description = "Component to create shipping table rates";

    /**
     * @var TablerateFactory
     */
    protected $tablerateFactory;

    /**
     * @var WebsiteFactory
     */
    protected $websiteFactory;

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * AdminRoles constructor.
     * @param LoggingInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param TablerateFactory $tablerateFactory
     * @param WebsiteFactory $websiteFactory
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        TablerateFactory $tablerateFactory,
        WebsiteFactory $websiteFactory,
        RegionFactory $regionFactory
    ) {
        parent::__construct($log, $objectManager);
        $this->tablerateFactory = $tablerateFactory;
        $this->websiteFactory = $websiteFactory;
        $this->regionFactory = $regionFactory;
    }


    /**
     * This method should be used to process the data and populate the Magento Database.
     *
     * @param array $data
     * @return void
     */
    public function processData(array $data = null)
    {
        /** @var Tablerate */
        $tablerateModel = $this->tablerateFactory->create();

        $shippingRateCount = 0;
        foreach ($data as $website => $shippingRates) {

            /** @var Website */
            $websiteModel = $this->websiteFactory->create();
            $websiteModel->load($website, 'code');
            $websiteId = $websiteModel->getId();

            if (!$websiteId) {
                $this->log->logError(sprintf("No website exists for code '%s'. Skipping.", $website));
                return;
            }

            foreach ($shippingRates as $shippingRate) {
                $this->createNewShippingTableRate(
                    $shippingRate,
                    $websiteId,
                    $shippingRateCount,
                    $website,
                    $tablerateModel
                );
            }
        }
    }

    /**
     * @param $shippingRate
     * @param $websiteId
     * @param $shippingRateCount
     * @param $website
     * @param $tablerateModel
     */
    private function createNewShippingTableRate(
        $shippingRate,
        $websiteId,
        $shippingRateCount,
        $website,
        $tablerateModel
    ) {
        $columns = [
            'website_id',
            'dest_region_id',
            'dest_country_id',
            'dest_zip',
            'condition_name',
            'condition_value',
            'price',
            'cost'
        ];

        /** @var Region */
        $regionModel = $this->regionFactory->create();
        $regionModel = $regionModel->loadByCode($shippingRate['dest_region_code'], $shippingRate['dest_country_id']);
        $regionId = $regionModel->getId();

        $this->removeYamlKeysFromDatabaseInsert($shippingRate);

        $shippingRate = array_merge(array('dest_region_id' => $regionId), $shippingRate);
        $shippingRate = array_merge(array('website_id' => $websiteId), $shippingRate);

        $this->log->logInfo(
            sprintf(
                "Shipping rate #%s for website %s being created",
                ++$shippingRateCount,
                $website
            )
        );
        $tablerateModel->getConnection()
            ->insertArray($tablerateModel->getMainTable(), $columns, array($shippingRate));
    }

    /**
     * @param array $shippingRate
     */
    private function removeYamlKeysFromDatabaseInsert(array &$shippingRate)
    {
        unset($shippingRate['dest_region_code']);
        unset($shippingRate['website_code']);
    }
}
