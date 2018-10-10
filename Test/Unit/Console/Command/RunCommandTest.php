<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Api\ConfigInterface;
use CtiDigital\Configurator\Api\ConfiguratorAdapterInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Model\Processor;
use CtiDigital\Configurator\Component\Factory\ComponentFactoryInterface;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RunCommandTest
 * @package CtiDigital\Configurator\Console\Command
 * @SuppressWarnings(PHPMD)
 */
class RunCommandTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var RunCommand
     */
    private $command;

    /**
     * @var InputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInput;

    /**
     * @var OutputInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mockOutput;

    /**
     * @var ConfiguratorAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $runCommandAdapter;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configInterface;

    /**
     * @var Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processor;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerInterface;

    /**
     * @var ComponentFactoryInterface
     */
    private $componentFactory;

    protected function setUp()
    {
        $this->runCommandAdapter = $this->getMockBuilder(ConfiguratorAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configInterface = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->componentFactory = $this->getMockBuilder(ComponentFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $consoleOutput = $this->getMockBuilder(ConsoleOutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $scopeInterface = $this->getMockBuilder(ScopeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $state = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([$scopeInterface])
            ->getMock();
        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([$consoleOutput])
            ->getMock();

        $this->processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->setConstructorArgs([
                $this->configInterface,
                $this->loggerInterface,
                $state,
                $this->componentFactory
            ])
            ->getMock();

        $this->command = new RunCommand(
            $this->runCommandAdapter,
            $this->configInterface,
            $this->processor
        );

        $this->mockInput = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockOutput = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testItIsAConsoleCommand()
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function testItHasTheCorrectName()
    {
        $this->assertSame('configurator:run', $this->command->getName());
    }
}
