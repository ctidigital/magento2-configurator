<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Service;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\ComponentListInterface;
use CtiDigital\Configurator\Api\FileComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Api\SourceDataParserInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Service\ComponentRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Service\ComponentRunner
 */
class ComponentRunnerTest extends TestCase
{
    private ComponentRunner $runner;

    /** @var ComponentListInterface&MockObject */
    private ComponentListInterface $componentList;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    /** @var SourceDataParserInterface&MockObject */
    private SourceDataParserInterface $parser;

    protected function setUp(): void
    {
        $this->componentList = $this->createMock(ComponentListInterface::class);
        $this->log           = $this->createMock(LoggerInterface::class);
        $this->parser        = $this->createMock(SourceDataParserInterface::class);

        $this->runner = new ComponentRunner($this->componentList, $this->log, $this->parser);
    }

    // ── Global sources ────────────────────────────────────────────────────────

    public function testExecuteRunsGlobalSourcesViaComponent(): void
    {
        $component = $this->createMock(ComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);

        $parsedData = ['key' => 'value'];
        $this->parser->expects($this->once())
            ->method('parse')
            ->with('path/to/source.yaml', null)
            ->willReturn($parsedData);

        $component->expects($this->once())
            ->method('execute')
            ->with($parsedData);

        $this->runner->execute(
            'websites',
            ['sources' => ['path/to/source.yaml']],
            'staging',
            false
        );
    }

    public function testExecutePassesRawPathToFileComponentInterface(): void
    {
        $component = $this->createMock(FileComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);

        // Parser should NOT be called for FileComponentInterface
        $this->parser->expects($this->never())->method('parse');

        $component->expects($this->once())
            ->method('execute')
            ->with('path/to/source.csv');

        $this->runner->execute(
            'products',
            ['sources' => ['path/to/source.csv']],
            'staging',
            false
        );
    }

    // ── Missing file handling ─────────────────────────────────────────────────

    public function testExecuteSkipsMissingFileWhenIgnoreFlagIsTrue(): void
    {
        $component = $this->createMock(ComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);

        $this->parser->method('parse')
            ->willThrowException(new ComponentException('Could not find file'));

        $this->log->expects($this->once())
            ->method('logInfo')
            ->with($this->stringContains('Skipping file'));

        $component->expects($this->never())->method('execute');

        $this->runner->execute(
            'config',
            ['sources' => ['missing.yaml']],
            'staging',
            true
        );
    }

    public function testExecuteRethrowsComponentExceptionWhenIgnoreFlagIsFalse(): void
    {
        $component = $this->createMock(ComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);

        $this->parser->method('parse')
            ->willThrowException(new ComponentException('Could not find file'));

        $this->expectException(ComponentException::class);

        $this->runner->execute(
            'config',
            ['sources' => ['missing.yaml']],
            'staging',
            false
        );
    }

    // ── Environment-specific sources ──────────────────────────────────────────

    public function testExecuteLogsCommentAndReturnsWhenNoEnvNode(): void
    {
        $component = $this->createMock(ComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);

        $this->parser->method('parse')->willReturn([]);
        $component->method('execute');

        // Capture all logComment calls — the header fires first with empty/border strings
        $loggedComments = [];
        $this->log->method('logComment')
            ->willReturnCallback(function (string $msg) use (&$loggedComments) {
                $loggedComments[] = $msg;
            });

        $this->runner->execute(
            'config',
            ['sources' => ['source.yaml']],
            'staging',
            false
        );

        $this->assertNotEmpty(
            array_filter($loggedComments, fn($m) => str_contains($m, 'No environment node'))
        );
    }

    public function testExecuteLogsCommentWhenEnvNodeMissingForCurrentEnv(): void
    {
        $component = $this->createMock(ComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);

        $this->parser->method('parse')->willReturn([]);
        $component->method('execute');

        $loggedComments = [];
        $this->log->method('logComment')
            ->willReturnCallback(function (string $msg) use (&$loggedComments) {
                $loggedComments[] = $msg;
            });

        $this->runner->execute(
            'config',
            ['sources' => ['source.yaml'], 'env' => ['production' => ['sources' => ['prod.yaml']]]],
            'staging',
            false
        );

        $this->assertNotEmpty(
            array_filter($loggedComments, fn($m) => str_contains($m, "No 'staging' environment specific node"))
        );
    }

    public function testExecuteProcessesEnvSpecificSourcesForMatchingEnvironment(): void
    {
        $component = $this->createMock(ComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);

        $globalData = ['global' => true];
        $envData    = ['env' => true];

        $this->parser->expects($this->exactly(2))
            ->method('parse')
            ->willReturnOnConsecutiveCalls($globalData, $envData);

        $component->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function ($data) use ($globalData, $envData) {
                static $calls = 0;
                $calls++;
                if ($calls === 1) {
                    $this->assertSame($globalData, $data);
                } else {
                    $this->assertSame($envData, $data);
                }
            });

        $this->runner->execute(
            'config',
            [
                'sources' => ['global.yaml'],
                'env'     => ['staging' => ['sources' => ['staging.yaml']]],
            ],
            'staging',
            false
        );
    }

    // ── Env-specific missing-file handling ────────────────────────────────────

    public function testExecuteSkipsMissingEnvFileWhenIgnoreFlagIsTrue(): void
    {
        $component = $this->createMock(ComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);

        $this->parser->method('parse')
            ->willThrowException(new ComponentException('Could not find env file'));

        $this->log->expects($this->once())
            ->method('logInfo')
            ->with($this->stringContains('Skipping file'));

        $component->expects($this->never())->method('execute');

        $this->runner->execute(
            'config',
            ['env' => ['staging' => ['sources' => ['missing-env.yaml']]]],
            'staging',
            true
        );
    }

    public function testExecuteRethrowsForEnvSourceWhenIgnoreFlagIsFalse(): void
    {
        $component = $this->createMock(ComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);

        $this->parser->method('parse')
            ->willThrowException(new ComponentException('Could not find env file'));

        $this->expectException(ComponentException::class);

        $this->runner->execute(
            'config',
            ['env' => ['staging' => ['sources' => ['missing-env.yaml']]]],
            'staging',
            false
        );
    }

    // ── Env node present but no 'sources' key ─────────────────────────────────

    public function testExecuteLogsCommentWhenEnvSourcesKeyMissing(): void
    {
        $component = $this->createMock(ComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);

        $this->parser->expects($this->never())->method('parse');
        $component->expects($this->never())->method('execute');

        $loggedComments = [];
        $this->log->method('logComment')
            ->willReturnCallback(function (string $msg) use (&$loggedComments) {
                $loggedComments[] = $msg;
            });

        $this->runner->execute(
            'config',
            ['env' => ['staging' => [/* no 'sources' key */]]],
            'staging',
            false
        );

        $this->assertNotEmpty(
            array_filter($loggedComments, fn($m) => str_contains($m, "No 'staging' environment specific sources"))
        );
    }
}
