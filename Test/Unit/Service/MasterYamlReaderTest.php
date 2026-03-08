<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Service;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\ComponentListInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Service\MasterYamlReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Service\MasterYamlReader
 */
class MasterYamlReaderTest extends TestCase
{
    private string $masterYamlPath;

    /** @var ComponentListInterface&MockObject */
    private ComponentListInterface $componentList;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    private MasterYamlReader $reader;

    protected function setUp(): void
    {
        // Use a temp file as master.yaml; BP is set to sys_get_temp_dir() in bootstrap.php
        $this->masterYamlPath = BP . '/app/etc/master.yaml';
        @mkdir(BP . '/app/etc', 0777, true);

        $this->componentList = $this->createMock(ComponentListInterface::class);
        $this->log           = $this->createMock(LoggerInterface::class);

        $this->reader = new MasterYamlReader($this->componentList, $this->log);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->masterYamlPath)) {
            unlink($this->masterYamlPath);
        }
    }

    private function writeMasterYaml(string $content): void
    {
        file_put_contents($this->masterYamlPath, $content);
    }

    private function makeRegisteredComponent(): ComponentInterface&MockObject
    {
        $component = $this->createMock(ComponentInterface::class);
        $this->componentList->method('getComponent')->willReturn($component);
        return $component;
    }

    // ── File existence ────────────────────────────────────────────────────────

    public function testReadThrowsWhenMasterYamlIsMissing(): void
    {
        if (file_exists($this->masterYamlPath)) {
            unlink($this->masterYamlPath);
        }

        $this->expectException(ComponentException::class);
        $this->expectExceptionMessageMatches('/Master YAML does not exist/');

        $this->reader->read();
    }

    // ── Validation: enabled node ──────────────────────────────────────────────

    public function testReadLogsErrorWhenEnabledNodeMissing(): void
    {
        $this->writeMasterYaml("websites:\n  sources:\n    - path/to/file.yaml\n");

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('"enabled" node'));

        $this->reader->read();
    }

    // ── Validation: sources ───────────────────────────────────────────────────

    public function testReadLogsErrorWhenNoSourcesDefined(): void
    {
        $this->writeMasterYaml("websites:\n  enabled: 1\n");

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('no data sources'));

        $this->reader->read();
    }

    // ── Validation: component registered ─────────────────────────────────────

    public function testReadLogsErrorWhenComponentNotRegistered(): void
    {
        $this->writeMasterYaml("notacomponent:\n  enabled: 1\n  sources:\n    - path/to/file.yaml\n");

        // getComponent returns false → isValidComponent returns false
        $this->componentList->method('getComponent')->willReturn(false);

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('not a valid component'));

        $this->reader->read();
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function testReadReturnsParsedConfigOnValidInput(): void
    {
        $this->writeMasterYaml(
            "websites:\n  enabled: 1\n  sources:\n    - path/to/websites.yaml\n"
        );
        $this->makeRegisteredComponent();

        $result = $this->reader->read();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('websites', $result);
        $this->assertEquals(1, $result['websites']['enabled']);
        $this->assertEquals(['path/to/websites.yaml'], $result['websites']['sources']);
    }

    public function testReadAcceptsEnvOnlySourcesAsValid(): void
    {
        $this->writeMasterYaml(
            "config:\n  enabled: 1\n  env:\n    staging:\n      sources:\n        - path/to/config.yaml\n"
        );
        $this->makeRegisteredComponent();

        // Should not log an error for missing global sources when env sources exist
        $this->log->expects($this->never())->method('logError');

        $result = $this->reader->read();

        $this->assertArrayHasKey('config', $result);
    }
}
