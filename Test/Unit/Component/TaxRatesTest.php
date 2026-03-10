<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\TaxRates;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResults;
use Magento\Tax\Api\Data\TaxRateInterface;
use Magento\Tax\Api\Data\TaxRateInterfaceFactory;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Component\TaxRates
 */
class TaxRatesTest extends TestCase
{
    /**
     * Re-created on every makeSut() call — never share between tests.
     *
     * @var TaxRateRepositoryInterface&MockObject
     */
    private TaxRateRepositoryInterface $taxRateRepository;

    /** @var TaxRateInterfaceFactory&MockObject */
    private TaxRateInterfaceFactory $taxRateFactory;

    /** @var SearchCriteriaBuilder&MockObject */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * Re-created on every makeSut() call — never share between tests.
     *
     * @var RegionFactory&MockObject
     */
    private RegionFactory $regionFactory;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    /** @var TaxRateInterface&MockObject */
    private TaxRateInterface $taxRate;

    protected function setUp(): void
    {
        $this->taxRateFactory        = $this->createMock(TaxRateInterfaceFactory::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->log                   = $this->createMock(LoggerInterface::class);

        // SearchCriteriaBuilder chain: addFilter()->create() -> SearchCriteria
        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn(new SearchCriteria());

        // Default mock rate returned by the factory — shared across all tests
        $this->taxRate = $this->createMock(TaxRateInterface::class);
        $this->taxRateFactory->method('create')->willReturn($this->taxRate);
    }

    /**
     * Creates a fresh TaxRates SUT with fresh repository and regionFactory mocks.
     *
     * PHPUnit stubs follow FIFO (first-configured-wins), so re-using the same mock
     * object across tests that need different getList() or create() return values
     * would cause earlier stubs to shadow later ones.  This factory always creates
     * brand-new mock instances, stored in $this->taxRateRepository / $this->regionFactory
     * so tests can set additional expectations after the SUT is built.
     *
     * @param int         $existingCount Number of existing rates getList() should report.
     * @param Region|null $regionMock    Custom region mock; defaults to a wildcard (id = 0).
     */
    private function makeSut(int $existingCount = 0, ?Region $regionMock = null): TaxRates
    {
        // Fresh repository mock — isolated from every other test
        $this->taxRateRepository = $this->createMock(TaxRateRepositoryInterface::class);
        $results                 = $this->createMock(SearchResults::class);
        $results->method('getTotalCount')->willReturn($existingCount);
        $this->taxRateRepository->method('getList')->willReturn($results);

        // Build a default wildcard region (id = 0) when no custom mock is supplied
        if ($regionMock === null) {
            $regionMock = $this->getMockBuilder(Region::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['loadByCode', 'getId'])
                ->getMock();
            $regionMock->method('loadByCode')->willReturnSelf();
            $regionMock->method('getId')->willReturn(0);
        }

        $this->regionFactory = $this->createMock(RegionFactory::class);
        $this->regionFactory->method('create')->willReturn($regionMock);

        return new TaxRates(
            $this->taxRateRepository,
            $this->taxRateFactory,
            $this->searchCriteriaBuilder,
            $this->regionFactory,
            $this->log
        );
    }

    // -- Alias -----------------------------------------------------------------

    public function testGetAliasReturnsTaxrates(): void
    {
        $this->assertSame('taxrates', $this->makeSut()->getAlias());
    }

    // -- Insufficient data -----------------------------------------------------

    public function testExecuteLogsErrorWhenDataHasHeaderRowOnly(): void
    {
        $sut = $this->makeSut();

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('no data'));

        $this->taxRateRepository->expects($this->never())->method('save');

        $sut->execute([
            ['code', 'tax_country_id', 'tax_region_id', 'tax_postcode', 'rate',
             'zip_is_range', 'zip_from', 'zip_to'],
        ]);
    }

    // -- Creates new rate ------------------------------------------------------

    public function testExecuteSavesRateWhenItDoesNotExist(): void
    {
        $sut = $this->makeSut(existingCount: 0);

        $this->taxRateRepository->expects($this->once())
            ->method('save')
            ->with($this->taxRate);

        $sut->execute($this->sampleData());
    }

    public function testExecuteSetsCorrectFieldsOnNewRate(): void
    {
        $sut = $this->makeSut(existingCount: 0);

        $this->taxRate->expects($this->once())->method('setCode')->with('VAT_GB_STANDARD');
        $this->taxRate->expects($this->once())->method('setTaxCountryId')->with('GB');
        $this->taxRate->expects($this->once())->method('setTaxPostcode')->with('*');
        $this->taxRate->expects($this->once())->method('setRate')->with(20.0);
        $this->taxRate->expects($this->once())->method('setTaxRegionId')->with(0);

        $sut->execute($this->sampleData());
    }

    // -- Skips existing rate ---------------------------------------------------

    public function testExecuteSkipsRateWhenAlreadyExists(): void
    {
        // Fresh SUT: getList() reports existingCount=1 → rate already exists
        $sut = $this->makeSut(existingCount: 1);

        $this->taxRateRepository->expects($this->never())->method('save');
        $this->log->expects($this->once())
            ->method('logComment')
            ->with($this->stringContains('already exists'));

        $sut->execute($this->sampleData());
    }

    // -- Error handling --------------------------------------------------------

    public function testExecuteLogsErrorWhenSaveThrowsException(): void
    {
        $sut = $this->makeSut(existingCount: 0);

        $this->taxRateRepository->method('save')
            ->willThrowException(new \Exception('DB write failed'));

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('DB write failed'));

        $sut->execute($this->sampleData());
    }

    // -- Region code lookup ----------------------------------------------------

    public function testExecuteResolvesNamedRegionCodeViaFactory(): void
    {
        // Build a specific region mock before constructing the SUT so that
        // makeSut() wires it into the regionFactory — no stub-stacking needed.
        $mockRegion = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByCode', 'getId'])
            ->getMock();
        $mockRegion->expects($this->once())
            ->method('loadByCode')
            ->with('CA', 'US')
            ->willReturnSelf();
        $mockRegion->method('getId')->willReturn(12);

        $sut = $this->makeSut(existingCount: 0, regionMock: $mockRegion);

        $this->taxRate->expects($this->once())->method('setTaxRegionId')->with(12);

        $sut->execute([
            ['code', 'tax_country_id', 'tax_region_id', 'tax_postcode', 'rate',
             'zip_is_range', 'zip_from', 'zip_to'],
            ['US_CA_RATE', 'US', 'CA', '*', '8.25', '0', '', ''],
        ]);
    }

    // -- Helpers ---------------------------------------------------------------

    /**
     * Minimal two-row dataset: header + one GB standard-rate row.
     * tax_region_id = '*' (all regions), tax_postcode = '*' (all postcodes).
     */
    private function sampleData(): array
    {
        return [
            ['code', 'tax_country_id', 'tax_region_id', 'tax_postcode', 'rate',
             'zip_is_range', 'zip_from', 'zip_to'],
            ['VAT_GB_STANDARD', 'GB', '*', '*', '20.0', '0', '', ''],
        ];
    }
}
