<?php

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Api\ConfigInterface;
use CtiDigital\Configurator\Component\Factory\ComponentFactory;
use CtiDigital\Configurator\Component\Factory\ComponentFactoryInterface;
use Magento\Framework\App\State;
use Magento\Framework\Config\ScopeInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configInterface;

    /**
     * @var ComponentFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockComponentFactory;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerInterface;

    protected function setUp()
    {
        $this->configInterface = $this->getMock(ConfigInterface::class);
        $this->mockComponentFactory = $this->getMockBuilder(ComponentFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $consoleOutput = $this->getMock(ConsoleOutputInterface::class);
        $scopeInterface = $this->getMock(ScopeInterface::class);
        $state = $this->getMock(State::class, array(), array($scopeInterface));
        $this->loggerInterface = $this->getMock(LoggerInterface::class, array(), array(
            $consoleOutput,
        ));

        $this->processor = $this->getMock(Processor::class, array(), array(
            $this->configInterface,
            $this->loggerInterface,
            $state,
            $this->mockComponentFactory,
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
