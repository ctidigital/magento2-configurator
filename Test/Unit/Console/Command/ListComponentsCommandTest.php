<?php

namespace CtiDigital\Configurator\Console\Command;

use Symfony\Component\Console\Command\Command;

class ListComponentsCommandTest extends \PHPUnit_Framework_TestCase {


    /**
     * @var ListComponentsCommand
     */
    private $command;


    protected function setUp()
    {
        $this->command = new ListComponentsCommand();
    }

    public function testItIsAConsoleCommand() {
        $this->assertInstanceOf(Command::class,$this->command);
    }
}