<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Model\Configurator\ConfigInterface;
use CtiDigital\Configurator\Model\ConfiguratorAdapterInterface;
use CtiDigital\Configurator\Model\LoggerInterface;
use CtiDigital\Configurator\Model\Processor;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RunCommandTest
 * @package CtiDigital\Configurator\Console\Command
 * @SuppressWarnings(PHPMD)
 */
class RunCommandTest extends \PHPUnit_Framework_TestCase
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
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManager;

    /**
     * @var Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processor;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerInterface;

    protected function setUp()
    {
        $this->runCommandAdapter = $this->getMock(ConfiguratorAdapterInterface::class);
        $this->configInterface = $this->getMock(ConfigInterface::class);
        $this->objectManager = $this->getMock(ObjectManagerInterface::class);
        $consoleOutput = $this->getMock(ConsoleOutputInterface::class);
        $scopeInterface = $this->getMock(ScopeInterface::class);
        $state = $this->getMock(State::class, array(), array($scopeInterface));
        $this->loggerInterface = $this->getMock(LoggerInterface::class, array(), array(
            $consoleOutput
        ));

        $this->processor = $this->getMock(Processor::class, array(), array(
            $this->configInterface,
            $this->objectManager,
            $this->loggerInterface,
            $state
        ));

        $this->command = new RunCommand(
            $this->runCommandAdapter,
            $this->configInterface,
            $this->objectManager,
            $this->processor
        );

        $this->mockInput = $this->getMock(InputInterface::class);
        $this->mockOutput = $this->getMock(OutputInterface::class);
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
