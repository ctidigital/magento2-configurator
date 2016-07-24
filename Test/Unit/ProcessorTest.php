<?php

namespace CtiDigital\Configurator\Model;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Processor
     */
    private $processor;

    protected function setUp()
    {
        $this->processor = new Processor();
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
