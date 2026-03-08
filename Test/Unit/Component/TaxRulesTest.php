<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\TaxRules;
use CtiDigital\Configurator\Exception\ComponentException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResults;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Api\Data\TaxClassInterfaceFactory;
use Magento\Tax\Api\Data\TaxRateInterface;
use Magento\Tax\Api\Data\TaxRuleInterface;
use Magento\Tax\Api\Data\TaxRuleInterfaceFactory;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Tax\Api\TaxRuleRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Component\TaxRules
 */
class TaxRulesTest extends TestCase
{
    private TaxRules $taxRules;

    /** @var TaxRuleRepositoryInterface&MockObject */
    private TaxRuleRepositoryInterface $taxRuleRepository;

    /** @var TaxRuleInterfaceFactory&MockObject */
    private TaxRuleInterfaceFactory $taxRuleDataFactory;

    /** @var TaxRateRepositoryInterface&MockObject */
    private TaxRateRepositoryInterface $taxRateRepository;

    /** @var TaxClassRepositoryInterface&MockObject */
    private TaxClassRepositoryInterface $taxClassRepository;

    /** @var TaxClassInterfaceFactory&MockObject */
    private TaxClassInterfaceFactory $taxClassDataFactory;

    /** @var SearchCriteriaBuilder&MockObject */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    protected function setUp(): void
    {
        $this->taxRuleRepository    = $this->createMock(TaxRuleRepositoryInterface::class);
        $this->taxRuleDataFactory   = $this->createMock(TaxRuleInterfaceFactory::class);
        $this->taxRateRepository    = $this->createMock(TaxRateRepositoryInterface::class);
        $this->taxClassRepository   = $this->createMock(TaxClassRepositoryInterface::class);
        $this->taxClassDataFactory  = $this->createMock(TaxClassInterfaceFactory::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->log                  = $this->createMock(LoggerInterface::class);

        // SearchCriteriaBuilder is fluent — addFilter() returns self, create() returns a criteria object
        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn(
            new \Magento\Framework\Api\SearchCriteria()
        );

        $this->taxRules = new TaxRules(
            $this->taxRuleRepository,
            $this->taxRuleDataFactory,
            $this->taxRateRepository,
            $this->taxClassRepository,
            $this->taxClassDataFactory,
            $this->searchCriteriaBuilder,
            $this->log
        );
    }

    // ── execute() guard clauses ───────────────────────────────────────────────

    public function testExecuteThrowsComponentExceptionWhenNoRowData(): void
    {
        $this->expectException(ComponentException::class);
        $this->taxRules->execute(null);
    }

    public function testExecuteThrowsComponentExceptionWhenDataZeroKeyMissing(): void
    {
        $this->expectException(ComponentException::class);
        $this->taxRules->execute([1 => ['code', 'priority']]);
    }

    public function testExecuteSkipsRowAndLogsErrorWhenCodeIsEmpty(): void
    {
        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('Code is a required field'));

        // Row 0 is header, row 1 has empty code
        $this->taxRules->execute([
            0 => ['code', 'priority', 'position', 'calculate_subtotal', 'tax_rate_ids',
                  'customer_tax_class_ids', 'product_tax_class_ids'],
            1 => ['', '0', '0', '0', 'UK Standard Rate', 'Retail Customer', 'Taxable Goods'],
        ]);
    }

    // ── Rate lookup via TaxRateRepository ────────────────────────────────────

    public function testExecuteQueriesTaxRateRepositoryForEachRateCode(): void
    {
        $mockRate = $this->createMock(TaxRateInterface::class);
        $mockRate->method('getId')->willReturn(5);

        $rateResults = $this->createMock(SearchResults::class);
        $rateResults->method('getItems')->willReturn([$mockRate]);

        $classResults = $this->createMock(SearchResults::class);
        $classResults->method('getItems')->willReturn([]);

        $ruleResults = $this->createMock(SearchResults::class);
        $ruleResults->method('getItems')->willReturn([]);

        // taxRateRepository called for each rate name, taxClassRepository for each class,
        // taxRuleRepository called for duplicate check
        $this->taxRateRepository->expects($this->atLeastOnce())
            ->method('getList')
            ->willReturn($rateResults);

        $mockClass = $this->createMock(TaxClassInterface::class);
        $mockClass->method('setClassName')->willReturnSelf();
        $mockClass->method('setClassType')->willReturnSelf();
        $mockClass->method('getClassId')->willReturn(3);
        $this->taxClassDataFactory->method('create')->willReturn($mockClass);
        $this->taxClassRepository->method('getList')->willReturn($classResults);
        $this->taxClassRepository->method('save')->willReturn($mockClass);

        $this->taxRuleRepository->method('getList')->willReturn($ruleResults);

        $mockRule = $this->createMock(TaxRuleInterface::class);
        $mockRule->method('setCode')->willReturnSelf();
        $mockRule->method('setPriority')->willReturnSelf();
        $mockRule->method('setPosition')->willReturnSelf();
        $mockRule->method('setCalculateSubtotal')->willReturnSelf();
        $mockRule->method('setTaxRateIds')->willReturnSelf();
        $mockRule->method('setCustomerTaxClassIds')->willReturnSelf();
        $mockRule->method('setProductTaxClassIds')->willReturnSelf();
        $this->taxRuleDataFactory->method('create')->willReturn($mockRule);
        $this->taxRuleRepository->method('save')->willReturn($mockRule);

        $this->taxRules->execute([
            0 => ['code', 'priority', 'position', 'calculate_subtotal', 'tax_rate_ids',
                  'customer_tax_class_ids', 'product_tax_class_ids'],
            1 => ['TEST_RULE', '0', '0', '0', 'UK Standard Rate', 'Retail Customer', 'Taxable Goods'],
        ]);
    }

    // ── Duplicate check via TaxRuleRepository ────────────────────────────────

    public function testExecuteLogsCommentAndSkipsSaveWhenRuleAlreadyExists(): void
    {
        $mockRate = $this->createMock(TaxRateInterface::class);
        $mockRate->method('getId')->willReturn(1);
        $rateResults = $this->createMock(SearchResults::class);
        $rateResults->method('getItems')->willReturn([$mockRate]);
        $this->taxRateRepository->method('getList')->willReturn($rateResults);

        $mockClass = $this->createMock(TaxClassInterface::class);
        $mockClass->method('getClassId')->willReturn(2);
        $classResults = $this->createMock(SearchResults::class);
        $classResults->method('getItems')->willReturn([$mockClass]);
        $this->taxClassRepository->method('getList')->willReturn($classResults);

        // Rule already exists
        $existingRule = $this->createMock(TaxRuleInterface::class);
        $ruleResults = $this->createMock(SearchResults::class);
        $ruleResults->method('getItems')->willReturn([$existingRule]);
        $this->taxRuleRepository->method('getList')->willReturn($ruleResults);

        // Save must NOT be called
        $this->taxRuleRepository->expects($this->never())->method('save');

        $loggedComments = [];
        $this->log->method('logComment')
            ->willReturnCallback(function (string $msg) use (&$loggedComments) {
                $loggedComments[] = $msg;
            });

        $this->taxRules->execute([
            0 => ['code', 'priority', 'position', 'calculate_subtotal', 'tax_rate_ids',
                  'customer_tax_class_ids', 'product_tax_class_ids'],
            1 => ['EXISTING_RULE', '0', '0', '0', 'UK Standard Rate', 'Retail Customer', 'Taxable Goods'],
        ]);

        $this->assertNotEmpty(
            array_filter($loggedComments, fn($m) => str_contains($m, 'already exists'))
        );
    }

    public function testExecuteSavesNewTaxRuleViaRepository(): void
    {
        $mockRate = $this->createMock(TaxRateInterface::class);
        $mockRate->method('getId')->willReturn(1);
        $rateResults = $this->createMock(SearchResults::class);
        $rateResults->method('getItems')->willReturn([$mockRate]);
        $this->taxRateRepository->method('getList')->willReturn($rateResults);

        $mockClass = $this->createMock(TaxClassInterface::class);
        $mockClass->method('getClassId')->willReturn(2);
        $classResults = $this->createMock(SearchResults::class);
        $classResults->method('getItems')->willReturn([$mockClass]);
        $this->taxClassRepository->method('getList')->willReturn($classResults);

        // No existing rule
        $emptyResults = $this->createMock(SearchResults::class);
        $emptyResults->method('getItems')->willReturn([]);
        $this->taxRuleRepository->method('getList')->willReturn($emptyResults);

        $mockRule = $this->createMock(TaxRuleInterface::class);
        $mockRule->method('setCode')->willReturnSelf();
        $mockRule->method('setPriority')->willReturnSelf();
        $mockRule->method('setPosition')->willReturnSelf();
        $mockRule->method('setCalculateSubtotal')->willReturnSelf();
        $mockRule->method('setTaxRateIds')->willReturnSelf();
        $mockRule->method('setCustomerTaxClassIds')->willReturnSelf();
        $mockRule->method('setProductTaxClassIds')->willReturnSelf();
        $this->taxRuleDataFactory->method('create')->willReturn($mockRule);

        // save() must be called exactly once with our rule
        $this->taxRuleRepository->expects($this->once())
            ->method('save')
            ->with($mockRule)
            ->willReturn($mockRule);

        $this->taxRules->execute([
            0 => ['code', 'priority', 'position', 'calculate_subtotal', 'tax_rate_ids',
                  'customer_tax_class_ids', 'product_tax_class_ids'],
            1 => ['NEW_RULE', '1', '0', '0', 'UK Standard Rate', 'Retail Customer', 'Taxable Goods'],
        ]);
    }

    // ── TaxClass — create new when not found ─────────────────────────────────

    public function testExecuteCreatesNewTaxClassWhenNotFoundInRepository(): void
    {
        $mockRate = $this->createMock(TaxRateInterface::class);
        $mockRate->method('getId')->willReturn(1);
        $rateResults = $this->createMock(SearchResults::class);
        $rateResults->method('getItems')->willReturn([$mockRate]);
        $this->taxRateRepository->method('getList')->willReturn($rateResults);

        // Class not found
        $emptyClassResults = $this->createMock(SearchResults::class);
        $emptyClassResults->method('getItems')->willReturn([]);

        $newClass = $this->createMock(TaxClassInterface::class);
        $newClass->method('setClassName')->willReturnSelf();
        $newClass->method('setClassType')->willReturnSelf();
        $newClass->method('getClassId')->willReturn(99);

        $this->taxClassDataFactory->method('create')->willReturn($newClass);
        $this->taxClassRepository->method('getList')->willReturn($emptyClassResults);
        // save() must be called to create the new class
        $this->taxClassRepository->expects($this->atLeastOnce())
            ->method('save')
            ->with($newClass)
            ->willReturn($newClass);

        $emptyRuleResults = $this->createMock(SearchResults::class);
        $emptyRuleResults->method('getItems')->willReturn([]);
        $this->taxRuleRepository->method('getList')->willReturn($emptyRuleResults);

        $mockRule = $this->createMock(TaxRuleInterface::class);
        $mockRule->method('setCode')->willReturnSelf();
        $mockRule->method('setPriority')->willReturnSelf();
        $mockRule->method('setPosition')->willReturnSelf();
        $mockRule->method('setCalculateSubtotal')->willReturnSelf();
        $mockRule->method('setTaxRateIds')->willReturnSelf();
        $mockRule->method('setCustomerTaxClassIds')->willReturnSelf();
        $mockRule->method('setProductTaxClassIds')->willReturnSelf();
        $this->taxRuleDataFactory->method('create')->willReturn($mockRule);
        $this->taxRuleRepository->method('save')->willReturn($mockRule);

        $this->taxRules->execute([
            0 => ['code', 'priority', 'position', 'calculate_subtotal', 'tax_rate_ids',
                  'customer_tax_class_ids', 'product_tax_class_ids'],
            1 => ['NEW_RULE', '0', '0', '0', 'UK Standard Rate', 'New Customer Class', 'New Product Class'],
        ]);
    }

    // ── getAttributesFromCsv ──────────────────────────────────────────────────

    public function testGetAttributesFromCsvReturnsValuesAsIndexedArray(): void
    {
        $result = $this->taxRules->getAttributesFromCsv(['code', 'priority', 'position']);
        $this->assertEquals(['code', 'priority', 'position'], $result);
    }

    // ── Alias / description ───────────────────────────────────────────────────

    public function testGetAliasReturnsTaxrules(): void
    {
        $this->assertEquals('taxrules', $this->taxRules->getAlias());
    }
}
