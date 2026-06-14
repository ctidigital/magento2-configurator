<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Model\Import;

use CtiDigital\Configurator\Api\ImportAdapterFactoryInterface;
use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Model\Import\Importer;
use CtiDigital\Configurator\Model\Import\ImportErrorService;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ImportFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImporterTest extends TestCase
{
    /** @var ImportFactory|MockObject */
    private $importFactory;

    /** @var ImportErrorService|MockObject */
    private $importErrorService;

    /** @var ImportAdapterFactoryInterface|MockObject */
    private $importAdapterFactory;

    /** @var Import|MockObject */
    private $importModel;

    /** @var ProcessingErrorAggregatorInterface|MockObject */
    private $errorAggregator;

    /** @var array<string, mixed> Captured settings passed to Import::setData() */
    private array $capturedSettings = [];

    protected function setUp(): void
    {
        $this->errorAggregator = $this->getMockBuilder(ProcessingErrorAggregatorInterface::class)
            ->getMock();

        $this->importModel = $this->getMockBuilder(Import::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'setData',
                'validateSource',
                'importSource',
                'getErrorAggregator',
                'getFormatedLogTrace',
                'invalidateIndex',
            ])
            ->getMock();
        $this->importModel->method('setData')->willReturnCallback(function ($settings) {
            $this->capturedSettings = $settings;
            return $this->importModel;
        });
        $this->importModel->method('validateSource')->willReturn(true);
        $this->importModel->method('importSource')->willReturn(true);
        $this->importModel->method('getErrorAggregator')->willReturn($this->errorAggregator);
        $this->importModel->method('getFormatedLogTrace')->willReturn('log-trace');

        $this->importFactory = $this->getMockBuilder(ImportFactory::class)
            ->onlyMethods(['create'])
            ->getMock();
        $this->importFactory->method('create')->willReturn($this->importModel);

        $this->importErrorService = $this->getMockBuilder(ImportErrorService::class)
            ->getMock();

        $this->importAdapterFactory = $this->createMock(ImportAdapterFactoryInterface::class);
        $this->importAdapterFactory->method('create')
            ->willReturn($this->createMock(AbstractSource::class));
    }

    private function createImporter(): Importer
    {
        return new Importer(
            $this->importFactory,
            $this->importErrorService,
            $this->importAdapterFactory
        );
    }

    public function testDefaultsToCatalogProductEntity(): void
    {
        // The Products component never sets an entity, so the default must be catalog_product.
        $importer = $this->createImporter();
        $importer->getImportModel();

        $this->assertSame('catalog_product', $this->capturedSettings['entity']);
        $this->assertSame(Import::BEHAVIOR_APPEND, $this->capturedSettings['behavior']);
    }

    public function testSetEntityCodeOverridesDefault(): void
    {
        $importer = $this->createImporter();
        $importer->setEntityCode('customer_composite');
        $importer->getImportModel();

        $this->assertSame('customer_composite', $this->capturedSettings['entity']);
    }

    public function testCannotChangeSettingsAfterModelInitialised(): void
    {
        $importer = $this->createImporter();
        $importer->getImportModel();

        $this->expectException(ComponentException::class);
        $importer->setBehavior(Import::BEHAVIOR_APPEND);
    }

    public function testProcessImportRunsValidationThenImport(): void
    {
        $this->errorAggregator->method('hasToBeTerminated')->willReturn(false);
        $this->importErrorService->method('getImportErrorMessages')->willReturn(['a warning']);
        $this->importModel->expects($this->once())->method('importSource');
        $this->importModel->expects($this->once())->method('invalidateIndex');

        $importer = $this->createImporter();
        $importer->processImport([['sku' => 'ABC', 'name' => 'Test']]);

        $this->assertSame(['a warning'], $importer->getErrorMessages());
        $this->assertSame('log-trace', $importer->getLogTrace());
    }

    public function testProcessImportThrowsWhenValidationTerminates(): void
    {
        $this->errorAggregator->method('hasToBeTerminated')->willReturn(true);
        $this->importErrorService->method('getImportErrorMessagesAsString')->willReturn('fatal error');

        $importer = $this->createImporter();

        $this->expectException(ComponentException::class);
        $this->expectExceptionMessage('fatal error');
        $importer->processImport([['sku' => 'ABC']]);
    }
}
