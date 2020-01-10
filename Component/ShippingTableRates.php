<?php
namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\TablerateFactory;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate;
use Magento\Store\Model\WebsiteFactory;
use Magento\Store\Model\Website;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\Region;

class ShippingTableRates implements ComponentInterface
{
    protected $alias = "shippingtablerates";
    protected $name = "Shipping Table Rates";
    protected $description = "Component to create and maintain Shipping Table Rates";

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
     * @var LoggerInterface
     */
    private $log;

    /**
     * ShippingTableRates constructor.
     * @param TablerateFactory $tablerateFactory
     * @param WebsiteFactory $websiteFactory
     * @param RegionFactory $regionFactory
     * @param LoggerInterface $log
     */
    public function __construct(
        TablerateFactory $tablerateFactory,
        WebsiteFactory $websiteFactory,
        RegionFactory $regionFactory,
        LoggerInterface $log
    ) {
        $this->tablerateFactory = $tablerateFactory;
        $this->websiteFactory = $websiteFactory;
        $this->regionFactory = $regionFactory;
        $this->log = $log;
    }

    /**
     * This method should be used to process the data and populate the Magento Database.
     *
     * @param array $data
     * @return void
     */
    public function execute($data = null)
    {
        /** @var Tablerate $tablerateModel */
        $tablerateModel = $this->tablerateFactory->create();

        $shippingRateCount = 1;
        foreach ($data as $website => $shippingRates) {

            /** @var Website $websiteModel */
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
                $shippingRateCount++;
            }
        }
    }

    /**
     * @param $shippingRate
     * @param $websiteId
     * @param $shippingRateCount
     * @param $website
     * @param Tablerate $tablerateModel
     */
    private function createNewShippingTableRate(
        $shippingRate,
        $websiteId,
        $shippingRateCount,
        $website,
        Tablerate $tablerateModel
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
        if ($regionId === null) {
            $regionId = 0;
        }

        $this->removeYamlKeysFromDatabaseInsert($shippingRate);

        $shippingRate = array_merge(
            [
                'website_id' => $websiteId,
                'dest_region_id' => $regionId
            ],
            $shippingRate
        );

        $this->log->logInfo(
            sprintf(
                "Shipping rate #%s for website %s being created",
                $shippingRateCount,
                $website
            )
        );
        $tablerateModel->getConnection()
            ->insertOnDuplicate($tablerateModel->getMainTable(), [$shippingRate], $columns);
    }
    /**
     * @param array $shippingRate
     */
    private function removeYamlKeysFromDatabaseInsert(array &$shippingRate)
    {
        unset($shippingRate['dest_region_code']);
        unset($shippingRate['website_code']);
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
