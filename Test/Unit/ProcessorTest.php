<?php

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Api\ComponentListInterface;
use Magento\Framework\App\State;
use Magento\Framework\Config\ScopeInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var ComponentListInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $componentList;

    /**
     * @var State|\PHPUnit\Framework\MockObject\MockObject
     */
    private $state;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerInterface;

    protected function setUp()
    {
        $consoleOutput = $this->getMockBuilder(ConsoleOutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeInterface = $this->getMockBuilder(ScopeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->componentList = $this->getMockBuilder(ComponentListInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([$scopeInterface])
            ->getMock();
        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([$consoleOutput])
            ->getMock();

        $this->processor = new Processor(
            $this->componentList,
            $this->state,
            $this->loggerInterface
        );
    }

    public function testICanSetAnEnvironment()
    {
        $environment = 'stage';
        $this->processor->setEnvironment($environment);
        $this->assertEquals($environment, $this->processor->getEnvironment());
    }

    public function testICanAddASingleComponent()
    {
        $component = 'websites';
        $this->processor->addComponent($component);
        $this->assertArrayHasKey($component, $this->processor->getComponents());
    }

    public function testICanAddMultipleComponents()
    {
        $components = ['website', 'config'];
        foreach ($components as $component) {
            $this->processor->addComponent($component);
        }
        $this->assertCount(2, $this->processor->getComponents());
    }
}
