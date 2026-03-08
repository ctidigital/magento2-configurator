<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Processor\SqlSplitProcessor;
use CtiDigital\Configurator\Component\Sql;
use Magento\Framework\Filesystem\DriverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Component\Sql
 */
class SqlTest extends TestCase
{
    private Sql $component;

    /** @var SqlSplitProcessor&MockObject */
    private SqlSplitProcessor $processor;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    /** @var DriverInterface&MockObject */
    private DriverInterface $driver;

    protected function setUp(): void
    {
        $this->processor = $this->createMock(SqlSplitProcessor::class);
        $this->log       = $this->createMock(LoggerInterface::class);
        $this->driver    = $this->createMock(DriverInterface::class);

        $this->component = new Sql(
            $this->processor,
            $this->log,
            $this->driver
        );
    }

    // ── Alias ─────────────────────────────────────────────────────────────────

    public function testGetAliasReturnsSql(): void
    {
        $this->assertSame('sql', $this->component->getAlias());
    }

    // ── Guard clause ──────────────────────────────────────────────────────────

    public function testExecuteIsNoOpWhenSqlKeyAbsent(): void
    {
        $this->processor->expects($this->never())->method('process');
        $this->driver->expects($this->never())->method('isExists');

        $this->component->execute(['other' => 'data']);
    }

    // ── File missing ──────────────────────────────────────────────────────────

    public function testExecuteLogsErrorAndSkipsWhenFileDoesNotExist(): void
    {
        $this->driver->method('isExists')->willReturn(false);

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('does not exist'));

        $this->processor->expects($this->never())->method('process');

        $this->component->execute([
            'sql' => ['my_query' => 'path/to/missing.sql'],
        ]);
    }

    // ── File exists ───────────────────────────────────────────────────────────

    public function testExecuteDelegatesExistingFileToProcessor(): void
    {
        $this->driver->method('isExists')->willReturn(true);

        $this->processor->expects($this->once())
            ->method('process')
            ->with('my_query', $this->stringContains('path/to/script.sql'));

        $this->component->execute([
            'sql' => ['my_query' => 'path/to/script.sql'],
        ]);
    }
}
