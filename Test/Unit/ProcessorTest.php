<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Api\ComponentListInterface;
use CtiDigital\Configurator\Api\ComponentRunnerInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Api\MasterYamlReaderInterface;
use CtiDigital\Configurator\Api\SourceDataParserInterface;
use Magento\Framework\App\State;
use Magento\Framework\Config\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
    private Processor $processor;

    /** @var ComponentListInterface&MockObject */
    private ComponentListInterface $componentList;

    /** @var State&MockObject */
    private State $state;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    /** @var MasterYamlReaderInterface&MockObject */
    private MasterYamlReaderInterface $masterYamlReader;

    /** @var SourceDataParserInterface&MockObject */
    private SourceDataParserInterface $parser;

    /** @var ComponentRunnerInterface&MockObject */
    private ComponentRunnerInterface $componentRunner;

    protected function setUp(): void
    {
        $scopeInterface = $this->createMock(ScopeInterface::class);

        $this->componentList    = $this->createMock(ComponentListInterface::class);
        $this->state            = $this->getMockBuilder(State::class)
            ->setConstructorArgs([$scopeInterface])
            ->getMock();
        $this->log              = $this->createMock(LoggerInterface::class);
        $this->masterYamlReader = $this->createMock(MasterYamlReaderInterface::class);
        $this->parser           = $this->createMock(SourceDataParserInterface::class);
        $this->componentRunner  = $this->createMock(ComponentRunnerInterface::class);

        $this->processor = new Processor(
            $this->componentList,
            $this->state,
            $this->log,
            $this->masterYamlReader,
            $this->parser,
            $this->componentRunner
        );
    }

    // ── Existing getters / setters ────────────────────────────────────────────

    public function testICanSetAnEnvironment(): void
    {
        $environment = 'stage';
        $this->processor->setEnvironment($environment);
        $this->assertEquals($environment, $this->processor->getEnvironment());
    }

    public function testICanAddASingleComponent(): void
    {
        $component = 'websites';
        $this->processor->addComponent($component);
        $this->assertArrayHasKey($component, $this->processor->getComponents());
    }

    public function testICanAddMultipleComponents(): void
    {
        $components = ['website', 'config'];
        foreach ($components as $component) {
            $this->processor->addComponent($component);
        }
        $this->assertCount(2, $this->processor->getComponents());
    }

    // ── run() orchestration ───────────────────────────────────────────────────

    public function testRunWithNoComponentsCallsMasterYamlReaderOnce(): void
    {
        $master = [
            'websites' => ['enabled' => 1, 'sources' => ['websites.yaml']],
        ];
        $this->masterYamlReader->expects($this->once())->method('read')->willReturn($master);

        // emulateAreaCode calls [$this, 'runComponent'] — simulate by invoking the callback
        $this->state->method('emulateAreaCode')
            ->willReturnCallback(function (string $area, callable $callback, array $args) {
                $callback(...$args);
            });

        $this->componentRunner->expects($this->once())->method('execute');

        $this->processor->setEnvironment('staging');
        $this->processor->run();
    }

    public function testRunWithNoComponentsSkipsDisabledComponents(): void
    {
        $master = [
            'websites' => ['enabled' => 0, 'sources' => ['websites.yaml']],
            'config'   => ['enabled' => 1, 'sources' => ['config.yaml']],
        ];
        $this->masterYamlReader->method('read')->willReturn($master);

        $this->state->method('emulateAreaCode')
            ->willReturnCallback(function (string $area, callable $callback, array $args) {
                $callback(...$args);
            });

        // Only config (enabled=1) should be run
        $this->componentRunner->expects($this->once())
            ->method('execute')
            ->with('config', $this->anything(), $this->anything(), $this->anything());

        $this->processor->setEnvironment('staging');
        $this->processor->run();
    }

    public function testRunWithSpecificComponentsCallsMasterYamlReaderOnce(): void
    {
        $master = [
            'websites' => ['enabled' => 1, 'sources' => ['websites.yaml']],
            'config'   => ['enabled' => 1, 'sources' => ['config.yaml']],
        ];
        $this->masterYamlReader->expects($this->once())->method('read')->willReturn($master);

        $this->state->method('emulateAreaCode')
            ->willReturnCallback(function (string $area, callable $callback, array $args) {
                $callback(...$args);
            });

        $this->componentRunner->expects($this->once())
            ->method('execute')
            ->with('websites', $this->anything(), $this->anything(), $this->anything());

        $this->processor->setEnvironment('staging');
        $this->processor->addComponent('websites');
        $this->processor->run();
    }

    // ── runComponent() delegation ─────────────────────────────────────────────

    public function testRunComponentDelegatesToComponentRunnerWithCorrectArgs(): void
    {
        $config = ['sources' => ['file.yaml']];

        $this->componentRunner->expects($this->once())
            ->method('execute')
            ->with('websites', $config, 'staging', false);

        $this->processor->setEnvironment('staging');
        $this->processor->runComponent('websites', $config);
    }

    public function testRunComponentPassesIgnoreMissingFilesFlag(): void
    {
        $config = ['sources' => ['file.yaml']];

        $this->componentRunner->expects($this->once())
            ->method('execute')
            ->with('websites', $config, 'staging', true);

        $this->processor->setEnvironment('staging');
        $this->processor->setIgnoreMissingFiles(true);
        $this->processor->runComponent('websites', $config);
    }

    // ── BC proxy methods ──────────────────────────────────────────────────────

    public function testIsSourceRemoteDelegatesToParser(): void
    {
        $this->parser->expects($this->once())
            ->method('isSourceRemote')
            ->with('https://example.com/data.yaml')
            ->willReturn(true);

        $this->assertTrue($this->processor->isSourceRemote('https://example.com/data.yaml'));
    }

    public function testGetRemoteDataDelegatesToParser(): void
    {
        $this->parser->expects($this->once())
            ->method('getRemoteData')
            ->with('https://example.com/data.yaml')
            ->willReturn('raw content');

        $this->assertEquals('raw content', $this->processor->getRemoteData('https://example.com/data.yaml'));
    }
}
