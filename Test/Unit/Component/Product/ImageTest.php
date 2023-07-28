<?php
namespace CtiDigital\Configurator\Test\Unit\Component\Product;

use CtiDigital\Configurator\Component\Product\Image;
use Magento\Framework\Filesystem;
use FireGento\FastSimpleImport\Model\Config;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\HTTP\ZendClientFactory;
use CtiDigital\Configurator\Api\LoggerInterface;

class ImageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Image
     */
    private $image;

    /**
     * @var Filesystem|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fileSystem;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    /**
     * @var ZendClientFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    private $httpFactoryMock;

    /**
     * @var ZendClient | \PHPUnit_Framework_MockObject_MockObject
     */
    private $httpMock;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $log;

    protected function setUp()
    {
        $this->fileSystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpMock = $this->getMockBuilder(ZendClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['setUri', 'request', 'getBody'])
            ->getMock();

        $this->httpFactoryMock = $this->getMockBuilder(ZendClientFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->httpFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->httpMock);

        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->image = new Image(
            $this->fileSystem,
            $this->config,
            $this->httpFactoryMock,
            $this->log
        );
    }

    public function testIsValueUrl()
    {
        $testUrl = "http://test.com/media/item.png";
        $testFilename = 'item.png';
        $this->assertNotFalse($this->image->isValueUrl($testUrl));
        $this->assertFalse($this->image->isValueUrl($testFilename));
    }

    public function testDownloadFile()
    {
        $this->httpMock->expects($this->any())->method('setUri')->willReturnSelf();
        $this->httpMock->expects($this->any())->method('request')->willReturnSelf();
        $this->httpMock->expects($this->any())->method('getBody')->willReturn('testbinarycontent');
        $this->assertEquals('testbinarycontent', $this->image->downloadFile('http://test.com/media/item.png'));
    }

    public function testGetFileName()
    {
        $testUrl = "http://test.com/media/item.png";
        $this->assertEquals('item.png', $this->image->getFileName($testUrl));
    }
}
