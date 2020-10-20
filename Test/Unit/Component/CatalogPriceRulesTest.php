<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <b.herba@ctidigital.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\ComponentProcessorInterface;
use CtiDigital\Configurator\Component\CatalogPriceRules;
use CtiDigital\Configurator\Component\CatalogPriceRules\CatalogPriceRulesProcessor;
use CtiDigital\Configurator\Api\LoggerInterface;

/**
 * Class CatalogPriceRulesTest
 * @codingStandardsIgnoreStart
 * @SuppressWarnings(PHPMD)
 */
class CatalogPriceRulesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CatalogPriceRules
     */
    private $catalogPriceRules;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $log;

    /**
     * @var ComponentProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockComponentProcessor;

    protected function setUp()
    {
        $this->mockComponentProcessor = $this->getMockBuilder(CatalogPriceRulesProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData', 'setConfig', 'process'])
            ->getMock();

        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogPriceRules = new CatalogPriceRules(
            $this->mockComponentProcessor,
            $this->log
        );
    }

    public function testProcessDataExecution()
    {
        $this->mockComponentProcessor->expects($this->once())
            ->method('setData')
            ->willReturn($this->mockComponentProcessor);

        $this->mockComponentProcessor->expects($this->once())
            ->method('setConfig')
            ->willReturn($this->mockComponentProcessor);

        $this->mockComponentProcessor->expects($this->once())
            ->method('process');

        $this->catalogPriceRules->execute();
    }
}
