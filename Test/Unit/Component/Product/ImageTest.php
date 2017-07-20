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

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->httpFactoryMock = $this->getMockBuilder('Magento\Framework\HTTP\ZendClientFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->component = $this->objectManager->getObject(
            Image::class,
            [
                'httpClientFactory' => $this->httpClientFactoryMock
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
        $httpMock = $this->getMock(
            'Magento\Framework\HTTP\ZendClient',
            ['setUri', 'request', 'getBody'],
            [],
            '',
            false
        );
        $httpMock->expects($this->any())->method('setUri')->willReturnSelf();
        $httpMock->expects($this->any())->method('request')->willReturnSelf();
        $httpMock->expects($this->any())->method('getBody')->willReturn('testbinarycontent');

        $this->httpClientFactoryMock->expects($this->atLeastOnce())->method('create')->willReturn($httpMock);

        $this->assertEquals('testbinarycontent', $this->component->downloadFile('http://test.com/media/item.png'));
    }

    public function testGetFileName()
    {
        $testUrl = "http://test.com/media/item.png";
        $this->assertEquals('item.png', $this->component->getFileName($testUrl));
    }
}
