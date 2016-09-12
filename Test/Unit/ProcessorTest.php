<?php

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Model\Configurator\ConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Processor
     */
    private $processor;
    private $configInterface;
    private $objectManagerInterface;
    private $loggingInterface;

    protected function setUp()
    {
        $this->configInterface = $this->getMock(ConfigInterface::class);
        $this->objectManagerInterface = $this->getMock(ObjectManagerInterface::class);
        $consoleOutput = $this->getMock(ConsoleOutputInterface::class);
        $scopeInterface = $this->getMock(ScopeInterface::class);
        $state = $this->getMock(State::class, array(), array($scopeInterface));
        $this->loggingInterface = $this->getMock(LoggingInterface::class, array(), array(
            $consoleOutput
        ));

        $this->processor = $this->getMock(Processor::class, array(), array(
            $this->configInterface,
            $this->objectManagerInterface,
            $this->loggingInterface,
            $state
        ));
    }

    public function testICanSetAnEnvironment()
    {
        $this->markTestSkipped("To do - Test we can set environments");
        $environment = 'stage';
        $this->processor->setEnvironment($environment);
        $this->assertEquals($environment, $this->processor->getEnvironment());
    }

    public function testICanAddASingleComponent()
    {
        $this->markTestSkipped("To do - Test a single component can be added");
        $component = 'websites';
        $this->processor->addComponent($component);
        $this->assertArrayHasKey($component, $this->processor->getComponents());
    }

    public function testICanAddMultipleComponents()
    {
        $this->markTestSkipped("To do - Test multiple components can be added");
        $components = ['website', 'config'];
        foreach ($components as $component) {
            $this->processor->addComponent($component);
        }
        $this->assertCount(2, $this->processor->getComponents());
    }
}
