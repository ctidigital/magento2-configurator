<?php
namespace CtiDigital\Configurator\Test\Unit\Component\Product;

use CtiDigital\Configurator\Model\Component\Product\Image;

class ImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var Image
     */
    protected $component;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpFactoryMock;

    /**
     * @var \Magento\Framework\HTTP\ZendClient | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpMock;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->httpMock = $this->getMockBuilder('Magento\Framework\HTTP\ZendClient')
            ->disableOriginalConstructor()
            ->setMethods(['setUri', 'request', 'getBody'])
            ->getMock();

        $this->httpFactoryMock = $this->getMockBuilder('Magento\Framework\HTTP\ZendClientFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->httpFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->httpMock);

        $this->component = $this->objectManager->getObject(
            Image::class,
            [
                'httpClientFactory' => $this->httpFactoryMock
            ]
        );
    }

    public function testIsValueUrl()
    {
        $testUrl = "http://test.com/media/item.png";
        $testFilename = 'item.png';
        $this->assertNotFalse($this->component->isValueUrl($testUrl));
        $this->assertFalse($this->component->isValueUrl($testFilename));
    }

    public function testDownloadFile()
    {
        $this->httpMock->expects($this->any())->method('setUri')->willReturnSelf();
        $this->httpMock->expects($this->any())->method('request')->willReturnSelf();
        $this->httpMock->expects($this->any())->method('getBody')->willReturn('testbinarycontent');
        $this->assertEquals('testbinarycontent', $this->component->downloadFile('http://test.com/media/item.png'));
    }

    public function testGetFileName()
    {
        $testUrl = "http://test.com/media/item.png";
        $this->assertEquals('item.png', $this->component->getFileName($testUrl));
    }
}
