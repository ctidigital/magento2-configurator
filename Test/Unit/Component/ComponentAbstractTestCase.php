<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\ComponentAbstract;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class ComponentAbstractTestCase
 * @package CtiDigital\Configurator\Test\Unit\Component
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
abstract class ComponentAbstractTestCase extends \PHPUnit\Framework\TestCase
{

    /* @var $component ComponentAbstract */
    protected $component;

    /* @var $className String */
    protected $className;

    /* @var $logInterface LoggerInterface */
    protected $logInterface;

    /* @var $json Json */
    protected $json;

    /* @var $testObjectManager \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    protected $testObjectManager;

    /** @var $objectManager \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    abstract protected function componentSetUp();

    protected function setUp()
    {
        $this->testObjectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->componentSetUp();
    }

    public function testItExtendsAbstract()
    {
        $this->assertInstanceOf(ComponentAbstract::class, $this->component);
    }

    public function testItHasAnAlias()
    {
        $this->assertClassHasAttribute('alias', $this->className);
        $this->assertNotEmpty(
            $this->component->getComponentAlias(),
            sprintf('No alias specified in component %s', $this->className)
        );
    }

    public function testItHasAName()
    {
        $this->assertClassHasAttribute('name', $this->className);
        $this->assertNotEmpty(
            $this->component->getComponentName(),
            sprintf('No name specified in component %s', $this->className)
        );
    }

    /**
     * @param $testSource
     * @param $expected
     *
     * @dataProvider isSourceRemoteDataProvider
     */
    public function testIsSourceRemote($testSource, $expected)
    {
        $this->assertEquals($expected, $this->component->isSourceRemote($testSource));
    }

    /**
     * @return array
     */
    public static function isSourceRemoteDataProvider()
    {
        return [
            ['https://www.test.com/remote-source.json', true],
            ['../configurator/Configuration/base-website-config.yaml', false],
            ['configurator/Configuration/example.csv', false]
        ];
    }
}
