<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\TaxRates;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\TaxImportExport\Model\Rate\CsvImportHandler;
use PHPUnit\Framework\TestCase;

class TaxRatesTest extends TestCase
{
    private TaxRates $taxRates;

    /** @var CsvImportHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $csvImportHandler;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $log;

    /** @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $driver;

    /** Minimal two-row data (header + one rate) used across execute() tests. */
    private array $sampleData = [
        ['code', 'tax_country_id', 'tax_region_id', 'tax_postcode', 'rate', 'zip_is_range', 'zip_from', 'zip_to'],
        ['TAX_US', 'US', '0', '*', '10.0', '0', '', ''],
    ];

    protected function setUp(): void
    {
        $this->csvImportHandler = $this->getMockBuilder(CsvImportHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['importFromCsvFile'])
            ->getMock();

        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->driver = $this->getMockBuilder(DriverInterface::class)
            ->getMock();
        $this->driver->method('fileOpen')->willReturn('stub_handle');

        $this->taxRates = new TaxRates(
            $this->csvImportHandler,
            $this->log,
            $this->driver
        );
    }

    public function testGetAliasReturnsTaxrates(): void
    {
        $this->assertSame('taxrates', $this->taxRates->getAlias());
    }

    public function testExecuteCallsImportHandlerWithTempFile(): void
    {
        $this->csvImportHandler->expects($this->once())
            ->method('importFromCsvFile')
            ->with($this->arrayHasKey('tmp_name'));

        $this->taxRates->execute($this->sampleData);
    }

    public function testExecuteDeletesTempFileAfterImport(): void
    {
        $this->driver->expects($this->once())->method('deleteFile');

        $this->taxRates->execute($this->sampleData);
    }

    public function testExecuteWritesCsvRowsViaDriver(): void
    {
        // sampleData has 2 rows (header + 1 rate) → getSortedData produces 2 rows → fileWrite called twice.
        $this->driver->expects($this->exactly(2))->method('fileWrite');

        $this->taxRates->execute($this->sampleData);
    }
}
