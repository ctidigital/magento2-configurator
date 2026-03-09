<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Media;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;
use PHPUnit\Framework\TestCase;

class MediaTest extends TestCase
{
    private Media $media;

    /** @var DirectoryList|\PHPUnit\Framework\MockObject\MockObject */
    private $directoryList;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $log;

    /** @var DriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $driver;

    protected function setUp(): void
    {
        $this->directoryList = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPath'])
            ->getMock();
        $this->directoryList->method('getPath')->willReturn('/tmp/media');

        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->driver = $this->getMockBuilder(DriverInterface::class)
            ->getMock();

        $this->media = new Media(
            $this->directoryList,
            $this->log,
            $this->driver
        );
    }

    public function testGetAliasReturnsMedia(): void
    {
        $this->assertSame('media', $this->media->getAlias());
    }

    public function testExecuteCreatesDirectoryWhenNotExists(): void
    {
        $this->driver->method('isExists')->willReturn(false);
        $this->driver->expects($this->once())
            ->method('createDirectory')
            ->with('/tmp/media/images', 0777);

        $this->media->execute(['images' => []]);
    }

    public function testExecuteLogsCommentWhenDirectoryAlreadyExists(): void
    {
        $this->driver->method('isExists')->willReturn(true);
        $this->driver->expects($this->never())->method('createDirectory');
        $this->log->expects($this->once())->method('logComment');

        $this->media->execute(['images' => []]);
    }

    public function testExecuteDownloadsFileWhenNotExists(): void
    {
        $this->driver->method('isExists')->willReturn(false);
        $this->driver->method('fileGetContents')->willReturn('binary-content');

        $this->driver->expects($this->once())
            ->method('fileGetContents')
            ->with('https://example.com/logo.png');
        $this->driver->expects($this->once())
            ->method('filePutContents');

        $this->media->execute([
            ['name' => 'logo.png', 'location' => 'https://example.com/logo.png'],
        ]);
    }

    public function testExecuteSkipsExistingFile(): void
    {
        $this->driver->method('isExists')->willReturn(true);
        $this->driver->expects($this->never())->method('fileGetContents');
        $this->log->expects($this->once())->method('logComment')
            ->with($this->stringContains('already exists'));

        $this->media->execute([
            ['name' => 'logo.png', 'location' => 'https://example.com/logo.png'],
        ]);
    }

    public function testExecuteLogsErrorWhenNameNotSet(): void
    {
        $this->driver->method('isExists')->willReturn(false);
        $this->log->expects($this->once())->method('logError');

        $this->media->execute([
            ['location' => 'https://example.com/logo.png'],
        ]);
    }
}
