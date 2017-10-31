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
        $rateFactory = $this->getMock(RateFactory::class, [], [], '', false);
        $classModelFactory = $this->getMock(ClassModelFactory::class);
        $ruleFactory = $this->getMock(RuleFactory::class);

        $this->component = new TaxRules(
            $this->logInterface,
            $this->objectManager,
            $rateFactory,
            $classModelFactory,
            $ruleFactory
        );
        $this->className = TaxRules::class;
    }
}
