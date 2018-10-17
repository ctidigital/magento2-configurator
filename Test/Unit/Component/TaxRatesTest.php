<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\TaxRates;
use Magento\TaxImportExport\Model\Rate\CsvImportHandler;

class TaxRatesTest extends ComponentAbstractTestCase
{
    protected function componentSetUp()
    {
        $csvImportHandler = $this->getMockBuilder(CsvImportHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = new TaxRates(
            $this->logInterface,
            $this->objectManager,
            $csvImportHandler
        );

        $this->className = TaxRates::class;
    }
}
