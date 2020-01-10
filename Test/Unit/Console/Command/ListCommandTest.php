<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Api\ConfigInterface;
use CtiDigital\Configurator\Api\ConfiguratorAdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommandTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var ListCommand
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
    private $listCommandAdapter;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configInterface;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManager;

    protected function setUp()
    {
        $this->listCommandAdapter = $this->getMockBuilder(ConfiguratorAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configInterface = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new ListCommand(
            $this->listCommandAdapter,
            $this->configInterface,
            $this->objectManager
        );

        $this->mockInput = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockOutput = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMocK();
    }

    public function testItIsAConsoleCommand()
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function testItHasTheCorrectName()
    {
        $this->assertSame('configurator:list', $this->command->getName());
    }
}
