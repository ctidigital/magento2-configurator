<?php

namespace CtiDigital\Configurator\Component;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\OfflineShipping\Model\ResourceModel\Carrier\Tablerate;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Parser;

/**
 * Class ShippingTablesRatesTest - Test to run against Shipping Table Rates
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ShippingTablesRatesTest extends TestCase
{
    private $shippingTableRatesYamlPath;
    const BASE_WEBSITE_ID = 1;
    const WEBSITE_ID_COLUMN = "website_id";
    const ACCEPTED_PRECISION_DELTA = 0.00001;
    /**
     * See websites.yaml file.
     */
    const A_TEST_WEBSITE_CODE = "base";
    const EXPECTED_SHIPPING_RATE_COUNT_DIFFERENT_ERROR_MESSAGE = "Expected number shipping rates %s."
    . "Actual number of shipping table rates %s";

    const NOT_ALL_EXPECTED_SHIPPING_RATES_FOUND_ERROR_MESSAGE = "Not all expected shipping rates were "
    . "found in the database. NB. dest_region_code changes to dest_region_id at runtime."
    . "Also check \$regionIdMap is correct. Expected %s. Actual %s.";

    /**
     * From directory_country_region table
     * @var array
     */
    private $regionIdMap = ['BER' => 82];

    /**
     * @var ShippingTableRates
     */
    private $shippingTableRatesComponent;

    /**
     * @var Website
     */
    private $websiteModel;

    /**
     * @var Tablerate
     */
    private $tableRateModel;

    public function setUp(): void
    {
        $this->shippingTableRatesYamlPath = sprintf(
            "%s/../../Samples/Components/ShippingTableRates/shippingtablerates.yaml",
            __DIR__
        );

        $this->shippingTableRatesComponent = Bootstrap::getObjectManager()
            ->get('CtiDigital\Configurator\Model\Component\ShippingTableRates');

        $tableRatesFactory = Bootstrap::getObjectManager()
            ->get('Magento\OfflineShipping\Model\ResourceModel\Carrier\TablerateFactory');

        $this->tableRateModel = $tableRatesFactory->create();

        $websiteFactory = Bootstrap::getObjectManager()
            ->get('Magento\Store\Model\WebsiteFactory');

        $this->websiteModel = $websiteFactory->create();
    }

    public function testShouldCreateNewShippingTableRatesFromYamlFile()
    {
        // given a yaml file containing ShippingTableRates
        // and using the "base" website (that is already created by magento)
        $yamlParser = new Parser();
        $expectedShippingRates = $yamlParser->parse(file_get_contents($this->shippingTableRatesYamlPath), true);

        // when we run the ShippingTableRates component with this file
        $this->shippingTableRatesComponent->processData($expectedShippingRates);

        /** @var AdapterInterface */
        $dbAdaptor = $this->tableRateModel->getConnection();

        // then it should generate shipping table rates in the database
        $select = $dbAdaptor->select();
        $select->where(self::WEBSITE_ID_COLUMN, self::BASE_WEBSITE_ID);
        $select->from($this->tableRateModel->getMainTable());

        $actualShippingTableRates = $dbAdaptor->fetchAll($select);

        $this->assertTableRatesAreTheSame(
            $expectedShippingRates[self::A_TEST_WEBSITE_CODE],
            $actualShippingTableRates
        );
    }

    private function assertTableRatesAreTheSame(
        $expectedShippingRates,
        $actualWebsiteShippingTableRates
    ) {
        $this->assertEquals(
            count($expectedShippingRates),
            count($actualWebsiteShippingTableRates),
            sprintf(
                self::EXPECTED_SHIPPING_RATE_COUNT_DIFFERENT_ERROR_MESSAGE,
                count($expectedShippingRates),
                count($actualWebsiteShippingTableRates)
            )
        );

        $foundShippingRates = 0;
        foreach ($expectedShippingRates as $expectedShippingRate) {
            if ($this->expectedShippingRateIsFoundInActualShippingRates(
                $expectedShippingRate,
                $actualWebsiteShippingTableRates
            )) {
                $foundShippingRates++;
            }
        }

        $this->assertEquals(
            count($expectedShippingRates),
            $foundShippingRates,
            sprintf(
                self::NOT_ALL_EXPECTED_SHIPPING_RATES_FOUND_ERROR_MESSAGE,
                var_export($expectedShippingRates, true),
                var_export($actualWebsiteShippingTableRates, true)
            )
        );
    }

    private function expectedShippingRateIsFoundInActualShippingRates(
        array $testShippingRate,
        array $actualWebsiteShippingTableRates
    ) {
        $found = false;
        foreach ($actualWebsiteShippingTableRates as $actualWebsiteShippingTableRate) {
            if ($this->isShippingRateTheSame($actualWebsiteShippingTableRate, $testShippingRate)) {
                $found = true;
            }
            continue;
        }
        return $found;
    }

    private function isShippingRateTheSame(array $actualWebsiteShippingTableRate, array $expectedShippingRate)
    {

        return (
            $actualWebsiteShippingTableRate['dest_country_id'] == $expectedShippingRate['dest_country_id']
            && $actualWebsiteShippingTableRate['dest_region_id'] == $this->regionIdMap[$expectedShippingRate['dest_region_code']]

            && $actualWebsiteShippingTableRate['dest_zip'] == $expectedShippingRate['dest_zip']
            && $actualWebsiteShippingTableRate['condition_name'] == $expectedShippingRate['condition_name']
            && $this->floatsAreEqualToPrecision(
                $actualWebsiteShippingTableRate['condition_value'],
                $expectedShippingRate['condition_value'],
                self::ACCEPTED_PRECISION_DELTA
            )
            && $this->floatsAreEqualToPrecision(
                $actualWebsiteShippingTableRate['price'],
                $expectedShippingRate['price'],
                self::ACCEPTED_PRECISION_DELTA
            )
            && $this->floatsAreEqualToPrecision(
                $actualWebsiteShippingTableRate['cost'],
                $expectedShippingRate['cost'],
                self::ACCEPTED_PRECISION_DELTA
            )
        );
    }

    private function floatsAreEqualToPrecision($float, $otherFloat, $deltaPrecision)
    {
        return abs($float - $otherFloat) < $deltaPrecision;
    }
}
