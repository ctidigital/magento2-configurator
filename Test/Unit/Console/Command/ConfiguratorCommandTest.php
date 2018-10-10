<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Api\ConfiguratorAdapterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfiguratorCommandTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var ConfiguratorCommand
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
    private $configuratorCommandAdapter;

    protected function setUp()
    {
        $this->configuratorCommandAdapter = $this->getMockBuilder(ConfiguratorAdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new ConfiguratorCommand($this->configuratorCommandAdapter);
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
        $this->assertSame('configurator', $this->command->getName());
    }
}
