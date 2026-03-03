<?php

namespace CtiDigital\Configurator\Test\Unit\Component\Product;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Product\Image;
use FireGento\FastSimpleImport\Model\Config;
use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\RequestException;
use Magento\Framework\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ImageTest extends TestCase
{
    /**
     * @var Image
     */
    private $image;

    /**
     * @var Filesystem|MockObject
     */
    private $fileSystem;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactory;

    /**
     * @var Client|MockObject
     */
    private $clientMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $log;

    protected function setUp(): void
    {
        $this->fileSystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->clientFactory = $this->getMockBuilder(ClientFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->clientFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->clientMock);

        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->image = new Image(
            $this->fileSystem,
            $this->config,
            $this->clientFactory,
            $this->log
        );
    }

    public function testIsValueUrl()
    {
        $testUrl = 'http://test.com/media/item.png';
        $testFilename = 'item.png';
        $this->assertNotFalse($this->image->isValueURL($testUrl));
        $this->assertFalse($this->image->isValueURL($testFilename));
    }

    public function testDownloadFile()
    {
        $bodyContent = 'testbinarycontent';

        $streamMock = $this->getMockBuilder(StreamInterface::class)
            ->getMock();
        $streamMock->expects($this->any())
            ->method('__toString')
            ->willReturn($bodyContent);

        $responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        $this->clientMock->expects($this->once())
            ->method('request')
            ->willReturn($responseMock);

        $this->assertEquals($streamMock, $this->image->downloadFile('http://test.com/media/item.png'));
    }

    public function testDownloadFileReturnsEmptyStringOnException()
    {
        $requestMock = $this->getMockBuilder(\Psr\Http\Message\RequestInterface::class)->getMock();
        $this->clientMock->expects($this->once())
            ->method('request')
            ->willThrowException(new RequestException('connection error', $requestMock));

        $this->log->expects($this->once())->method('logError');

        $result = $this->image->downloadFile('http://test.com/media/item.png');
        $this->assertSame('', $result);
    }

    public function testGetFileName()
    {
        $testUrl = 'http://test.com/media/item.png';
        $this->assertEquals('item.png', $this->image->getFileName($testUrl));
    }

    public function testGetFileNameDecodesUrlEntities()
    {
        $testUrl = 'http://test.com/media/my%20image.png';
        $this->assertEquals('my-image.png', $this->image->getFileName($testUrl));
    }

    public function testGetFileNameForPlaceholderUrl()
    {
        $testUrl = 'http://placehold.it/300x200/jpg';
        $this->assertEquals('300x200.jpg', $this->image->getFileName($testUrl));
    }
}
