<?php
declare(strict_types=1);

/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <b.herba@ctidigital.com>
 * @copyright 2017 CtiDigital
 */

/**
 * RuleInterfaceFactory is a Magento DI-generated class that is only available after
 * running setup:di:compile. To allow getMockBuilder() to reflect the class in unit
 * tests, we declare a minimal stub here. The class_exists guard prevents a fatal error
 * if the class happens to be available in a compiled environment.
 */
namespace Magento\CatalogRule\Api\Data {
    if (!class_exists(\Magento\CatalogRule\Api\Data\RuleInterfaceFactory::class, false)) {
        class RuleInterfaceFactory
        {
            public function create(array $data = []): \Magento\CatalogRule\Api\Data\RuleInterface
            {
                throw new \RuntimeException('Stub only - this method should be mocked in tests.');
            }
        }
    }
}

namespace CtiDigital\Configurator\Test\Unit\Component\CatalogPriceRules {

    use CtiDigital\Configurator\Component\CatalogPriceRules\CatalogPriceRulesProcessor;
    use CtiDigital\Configurator\Model\Logging;
    use Magento\CatalogRule\Api\CatalogRuleRepositoryInterface;
    use Magento\CatalogRule\Api\Data\RuleInterfaceFactory;
    use Magento\CatalogRule\Model\Rule;
    use Magento\CatalogRule\Model\ResourceModel\Rule\Collection as RuleCollection;
    use Magento\CatalogRule\Model\Rule\Job;
    use Magento\Framework\Exception\CouldNotSaveException;
    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\TestCase;

    /**
     * @SuppressWarnings(PHPMD)
     */
    class CatalogPriceRulesTest extends TestCase
    {
        private CatalogPriceRulesProcessor $processor;

        /** @var Logging&MockObject */
        private MockObject $mockLogger;

        /** @var RuleInterfaceFactory&MockObject */
        private MockObject $mockRuleFactory;

        /** @var CatalogRuleRepositoryInterface&MockObject */
        private MockObject $mockRuleRepository;

        /** @var Job&MockObject */
        private MockObject $mockJob;

        protected function setUp(): void
        {
            $this->mockLogger = $this->getMockBuilder(Logging::class)
                ->disableOriginalConstructor()
                ->getMock();

            $this->mockRuleFactory = $this->getMockBuilder(RuleInterfaceFactory::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['create'])
                ->getMock();

            $this->mockRuleRepository = $this->getMockBuilder(CatalogRuleRepositoryInterface::class)
                ->getMock();

            $this->mockJob = $this->getMockBuilder(Job::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['applyAll'])
                ->getMock();

            $this->processor = new CatalogPriceRulesProcessor(
                $this->mockLogger,
                $this->mockRuleFactory,
                $this->mockRuleRepository,
                $this->mockJob
            );
        }

        /**
         * Build a Rule mock that returns the given collection from getCollection().
         */
        private function buildRuleWithCollection(MockObject $collection): MockObject
        {
            $rule = $this->getMockBuilder(Rule::class)
                ->disableOriginalConstructor()
                ->getMock();
            $rule->method('getCollection')->willReturn($collection);
            return $rule;
        }

        /**
         * Build a RuleCollection mock with the given size and first item.
         */
        private function buildCollection(int $size, MockObject $firstItem): MockObject
        {
            $collection = $this->getMockBuilder(RuleCollection::class)
                ->disableOriginalConstructor()
                ->getMock();
            $collection->method('addFieldToFilter')->willReturnSelf();
            $collection->method('getSize')->willReturn($size);
            $collection->method('getFirstItem')->willReturn($firstItem);
            return $collection;
        }

        public function testProcessingEmptyRulesData(): void
        {
            $this->mockRuleFactory->expects($this->never())->method('create');
            $this->mockRuleRepository->expects($this->never())->method('save');

            $loggedMessages = [];
            $this->mockLogger->method('logInfo')
                ->willReturnCallback(function (string $message) use (&$loggedMessages): void {
                    $loggedMessages[] = $message;
                });

            $this->processor->setData([])->process();

            $this->assertContains('Initializing configuration of Catalog Price Rules.', $loggedMessages);
            $this->assertContains('Catalog price rules configuration completed.', $loggedMessages);
        }

        public function testValidRuleProcessing(): void
        {
            $rules = [
                'rule1' => ['name' => 'Test Rule', 'is_active' => 1, 'discount_amount' => 20],
            ];

            // Existing rule (getId returns non-null → update path, no second create() call)
            $existingRule = $this->getMockBuilder(Rule::class)->disableOriginalConstructor()->getMock();
            $existingRule->method('getId')->willReturn('1');
            $existingRule->method('getData')->willReturn([]);

            $collection = $this->buildCollection(1, $existingRule);
            $ruleForLookup = $this->buildRuleWithCollection($collection);

            $this->mockRuleFactory->expects($this->once())
                ->method('create')
                ->willReturn($ruleForLookup);

            $this->mockRuleRepository->expects($this->once())
                ->method('save')
                ->with($existingRule);

            $this->processor->setData($rules)->setConfig([])->process();
        }

        public function testProcessLogErrorWhenRuleSaveException(): void
        {
            $errMsg = __('some error msg');
            $rules = ['rule1' => ['name' => 'Test Rule']];

            $existingRule = $this->getMockBuilder(Rule::class)->disableOriginalConstructor()->getMock();
            $existingRule->method('getId')->willReturn('1');
            $existingRule->method('getData')->willReturn([]);

            $collection = $this->buildCollection(1, $existingRule);
            $ruleForLookup = $this->buildRuleWithCollection($collection);

            $this->mockRuleFactory->method('create')->willReturn($ruleForLookup);

            $this->mockRuleRepository->expects($this->once())
                ->method('save')
                ->willThrowException(new CouldNotSaveException($errMsg));

            $this->mockLogger->expects($this->atLeastOnce())
                ->method('logError')
                ->with((string) $errMsg);

            $this->processor->setData($rules)->setConfig([])->process();
        }

        public function testApplyingRules(): void
        {
            $this->mockJob->expects($this->once())->method('applyAll');

            $this->processor->setData([])->setConfig(['apply_all' => true])->process();
        }
    }
}
