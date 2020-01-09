<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\TaxRules;
use Magento\Tax\Model\Calculation\RuleFactory;
use Magento\Tax\Model\Calculation\RateFactory;
use Magento\Tax\Model\ClassModelFactory;

class TaxRulesTest extends ComponentAbstractTestCase
{
    protected function componentSetUp()
    {
        $rateFactory = $this->getMockBuilder(RateFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $classModelFactory = $this->getMockBuilder(ClassModelFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $ruleFactory = $this->getMockBuilder(RuleFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = new TaxRules(
            $this->logInterface,
            $this->objectManager,
            $this->json,
            $rateFactory,
            $classModelFactory,
            $ruleFactory
        );
        $this->className = TaxRules::class;
    }
}
