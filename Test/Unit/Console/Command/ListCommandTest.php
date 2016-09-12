<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Model\Configurator\ConfigInterface;
use CtiDigital\Configurator\Model\ConfiguratorAdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommandTest extends \PHPUnit_Framework_TestCase
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
    private $objectManager = array();

    protected function setUp()
    {
        $this->listCommandAdapter = $this->getMock(ConfiguratorAdapterInterface::class);
        $this->configInterface = $this->getMock(ConfigInterface::class);
        $this->objectManager = $this->getMock(ObjectManagerInterface::class);

        $this->command = new ListCommand(
            $this->listCommandAdapter,
            $this->configInterface,
            $this->objectManager
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
        $this->assertSame('configurator:list', $this->command->getName());
    }
}
