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

/**
 * Class CatalogPriceRulesTest
 * @codingStandardsIgnoreStart
 * @SuppressWarnings(PHPMD)
 */
class CatalogPriceRulesTest extends ComponentAbstractTestCase
{
    /**
     * @var ComponentProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockComponentProcessor;

    protected function componentSetUp()
    {
        $this->mockComponentProcessor = $this->getMockBuilder(CatalogPriceRulesProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData', 'setConfig', 'process'])
            ->getMock();

        $this->component = new CatalogPriceRules(
            $this->logInterface,
            $this->objectManager,
            $this->mockComponentProcessor
        );

        $this->className = CatalogPriceRules::class;
    }

    public function testProcessDataExecution()
    {
        $this->markTestSkipped('Test will be skipped until CI configuration will be fixed');

        $this->mockComponentProcessor->expects($this->once())
            ->method('setData')
            ->willReturn($this->mockComponentProcessor);

        $this->mockComponentProcessor->expects($this->once())
            ->method('setConfig')
            ->willReturn($this->mockComponentProcessor);

        $this->mockComponentProcessor->expects($this->once())
            ->method('process');

        $this->component->process();
    }
}
