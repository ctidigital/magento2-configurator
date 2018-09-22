<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Config;
use Magento\Framework\Encryption\EncryptorInterface;

class ConfigTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $collectionFactory = $this->getMockBuilder('\Magento\Theme\Model\ResourceModel\Theme\CollectionFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $encrypterInterface = $this->getMockBuilder(EncryptorInterface::class)->getMock();
        $this->component = new Config($this->logInterface, $this->objectManager, $collectionFactory, $encrypterInterface);
        $this->className = Config::class;
    }

    /**
     * Test check if the path is the configuration path without an ID
     */
    public function testIsConfigTheme()
    {
        /**
         * @var Config $config
         */
        $config = $this->testObjectManager->getObject(Config::class);
        $this->assertTrue($config->isConfigTheme($config::PATH_THEME_ID, 'theme'));
    }

    /**
     * Test ignoring a path that has an ID for the config path
     */
    public function testIsConfigThemeWithAnId()
    {
        /**
         * @var Config $config
         */
        $config = $this->testObjectManager->getObject(Config::class);
        $this->assertTrue($config->isConfigTheme($config::PATH_THEME_ID, '1'));
    }

    /**
     * Test ignoring non-config theme path
     */
    public function testNotConfigTheme()
    {
        /**
         * @var Config $config
         */
        $config = $this->testObjectManager->getObject(Config::class);
        $this->assertFalse($config->isConfigTheme('a/path', '1'));
    }

    /**
     * Test getting theme by the path
     */
    public function testGetThemeById()
    {
        $mockThemeModel = $this->getMockBuilder('Magento\Theme\Model\Theme')
            ->disableOriginalConstructor()
            ->setMethods(['getThemeId'])
            ->getMock();

        $mockThemeModel->expects($this->once())
            ->method('getThemeId')
            ->willReturn(3);

        $mockFactory = $this->getMockBuilder('Magento\Theme\Model\ResourceModel\Theme\CollectionFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $mockCollection = $this->getMockBuilder('Magento\Theme\Model\ResourceModel\Theme\Collection')
            ->disableOriginalConstructor()
            ->setMethods(['getThemeByFullPath'])
            ->getMock();

        $mockCollection->expects($this->once())
            ->method('getThemeByFullPath')
            ->with('frontend/test/theme')
            ->willReturn($mockThemeModel);

        $mockFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockCollection);

        $config = $this->testObjectManager->getObject(
            Config::class,
            [
                'collectionFactory' => $mockFactory
            ]
        );

        $this->assertEquals(3, $config->getThemeIdByPath('frontend/test/theme'));
    }
}
