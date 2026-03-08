<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Config;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\Config as ScopeConfig;
use Magento\Theme\Model\ResourceModel\Theme\Collection;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\WebsiteFactory;
use CtiDigital\Configurator\Api\LoggerInterface;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigResource|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configResource;

    /**
     * @var ScopeConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfig;

    /**
     * @var ScopeConfig\Initial|\PHPUnit\Framework\MockObject\MockObject
     */
    private $initialConfig;

    /**
     * @var Collection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collection;

    /**
     * @var CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionFactory;

    /**
     * @var EncryptorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $encryptorInterface;

    /**
     * @var WebsiteFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteFactory;

    /**
     * @var StoreFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $log;

    protected function setUp(): void
    {
        $this->configResource = $this->getMockBuilder(ConfigResource::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeConfig = $this->getMockBuilder(ScopeConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->initialConfig = $this->getMockBuilder(ScopeConfig\Initial::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->collectionFactory->expects($this->any())->method('create')->willReturn($this->collection);
        $this->encryptorInterface = $this->getMockBuilder(EncryptorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteFactory = $this->getMockBuilder(WebsiteFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeFactory = $this->getMockBuilder(StoreFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = new Config(
            $this->configResource,
            $this->scopeConfig,
            $this->initialConfig,
            $this->collectionFactory,
            $this->encryptorInterface,
            $this->websiteFactory,
            $this->storeFactory,
            $this->log
        );
    }

    /**
     * Test check if the path is the configuration path without an ID
     */
    public function testIsConfigTheme()
    {
        $this->assertTrue($this->config->isConfigTheme(Config::PATH_THEME_ID, 'theme'));
    }

    /**
     * Test ignoring a path that has an ID for the config path
     */
    public function testIsConfigThemeWithAnId()
    {
        $this->assertTrue($this->config->isConfigTheme(Config::PATH_THEME_ID, '1'));
    }

    /**
     * Test ignoring non-config theme path
     */
    public function testNotConfigTheme()
    {
        $this->assertFalse($this->config->isConfigTheme('a/path', '1'));
    }

    /**
     * Test getting theme by the path
     */
    public function testGetThemeById()
    {
        $mockThemeModel = $this->getMockBuilder('Magento\Theme\Model\Theme')
            ->disableOriginalConstructor()
            ->addMethods(['getThemeId'])
            ->getMock();

        $mockThemeModel->expects($this->once())
            ->method('getThemeId')
            ->willReturn(3);

        $this->collection->expects($this->once())
            ->method('getThemeByFullPath')
            ->with('frontend/test/theme')
            ->willReturn($mockThemeModel);

        $this->assertEquals(3, $this->config->getThemeIdByPath('frontend/test/theme'));
    }

    // ── execute() ─────────────────────────────────────────────────────────────

    public function testExecuteLogsErrorForInvalidScope(): void
    {
        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('invalid_scope'));

        $this->config->execute(['invalid_scope' => []]);
    }

    public function testExecuteSavesNewGlobalConfig(): void
    {
        $this->initialConfig->method('getMetadata')->willReturn([]);
        $this->scopeConfig->method('getValue')->willReturn('old_value');

        $this->configResource->expects($this->once())
            ->method('saveConfig')
            ->with('general/locale/code', 'en_US', 'default', 0);

        $this->config->execute([
            'global' => [
                ['path' => 'general/locale/code', 'value' => 'en_US'],
            ],
        ]);
    }

    public function testExecuteSkipsGlobalConfigWhenValueUnchanged(): void
    {
        $this->initialConfig->method('getMetadata')->willReturn([]);
        $this->scopeConfig->method('getValue')->willReturn('en_US');

        $this->configResource->expects($this->never())->method('saveConfig');

        $this->config->execute([
            'global' => [
                ['path' => 'general/locale/code', 'value' => 'en_US'],
            ],
        ]);
    }

    public function testExecuteSavesWebsiteConfig(): void
    {
        $this->initialConfig->method('getMetadata')->willReturn([]);
        $this->scopeConfig->method('getValue')->willReturn('old_value');

        $mockWebsite = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId'])
            ->getMock();
        $mockWebsite->method('load')->willReturnSelf();
        $mockWebsite->method('getId')->willReturn(5);
        $this->websiteFactory->method('create')->willReturn($mockWebsite);

        $this->configResource->expects($this->once())
            ->method('saveConfig')
            ->with('general/locale/code', 'fr_FR', 'websites', 5);

        $this->config->execute([
            'websites' => [
                'base' => [
                    ['path' => 'general/locale/code', 'value' => 'fr_FR'],
                ],
            ],
        ]);
    }

    public function testExecuteLogsErrorWhenWebsiteNotFound(): void
    {
        $this->initialConfig->method('getMetadata')->willReturn([]);

        $mockWebsite = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId'])
            ->getMock();
        $mockWebsite->method('load')->willReturnSelf();
        $mockWebsite->method('getId')->willReturn(null);
        $this->websiteFactory->method('create')->willReturn($mockWebsite);

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('no website'));

        $this->config->execute([
            'websites' => [
                'missing_site' => [
                    ['path' => 'general/locale/code', 'value' => 'en_US'],
                ],
            ],
        ]);
    }

    public function testExecuteSavesStoreConfig(): void
    {
        $this->initialConfig->method('getMetadata')->willReturn([]);
        $this->scopeConfig->method('getValue')->willReturn('old_value');

        $mockStore = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId'])
            ->getMock();
        $mockStore->method('load')->willReturnSelf();
        $mockStore->method('getId')->willReturn(3);
        $this->storeFactory->method('create')->willReturn($mockStore);

        $this->configResource->expects($this->once())
            ->method('saveConfig')
            ->with('general/locale/code', 'de_DE', 'stores', 3);

        $this->config->execute([
            'stores' => [
                'default' => [
                    ['path' => 'general/locale/code', 'value' => 'de_DE'],
                ],
            ],
        ]);
    }

    public function testExecuteLogsErrorWhenStoreNotFound(): void
    {
        $this->initialConfig->method('getMetadata')->willReturn([]);

        $mockStore = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId'])
            ->getMock();
        $mockStore->method('load')->willReturnSelf();
        $mockStore->method('getId')->willReturn(null);
        $this->storeFactory->method('create')->willReturn($mockStore);

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('no store view'));

        $this->config->execute([
            'stores' => [
                'missing_store' => [
                    ['path' => 'general/locale/code', 'value' => 'en_US'],
                ],
            ],
        ]);
    }
}
