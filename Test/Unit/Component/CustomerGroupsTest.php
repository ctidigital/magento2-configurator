<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\CustomerGroups;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Data\Group;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResults;
use Magento\Tax\Model\ClassModel;
use Magento\Tax\Model\ClassModelFactory;
use Magento\Tax\Model\ResourceModel\TaxClass\Collection as TaxClassCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Component\CustomerGroups
 */
class CustomerGroupsTest extends TestCase
{
    private CustomerGroups $component;

    /** @var GroupRepositoryInterface&MockObject */
    private GroupRepositoryInterface $groupRepository;

    /** @var GroupInterfaceFactory&MockObject */
    private GroupInterfaceFactory $groupDataFactory;

    /** @var SearchCriteriaBuilder&MockObject */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /** @var ClassModelFactory&MockObject */
    private ClassModelFactory $classModelFactory;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $log;

    protected function setUp(): void
    {
        $this->groupRepository       = $this->createMock(GroupRepositoryInterface::class);
        $this->groupDataFactory      = $this->createMock(GroupInterfaceFactory::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->classModelFactory     = $this->createMock(ClassModelFactory::class);
        $this->log                   = $this->createMock(LoggerInterface::class);

        $this->component = new CustomerGroups(
            $this->groupRepository,
            $this->groupDataFactory,
            $this->searchCriteriaBuilder,
            $this->classModelFactory,
            $this->log
        );
    }

    // ── Alias ─────────────────────────────────────────────────────────────────

    public function testGetAliasReturnsCustomergroups(): void
    {
        $this->assertSame('customergroups', $this->component->getAlias());
    }

    // ── getTaxClassIdFromName ─────────────────────────────────────────────────

    public function testExecuteLogsErrorWhenTaxClassNotFound(): void
    {
        $taxClassItem = new class {
            public function getId(): mixed { return null; }
        };

        $collection = $this->createMock(TaxClassCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getFirstItem')->willReturn($taxClassItem);

        $classModel = $this->createMock(ClassModel::class);
        $classModel->method('getCollection')->willReturn($collection);

        $this->classModelFactory->method('create')->willReturn($classModel);

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('no Tax class'));

        $this->groupRepository->expects($this->never())->method('save');

        $this->component->execute([
            'customergroups' => [
                ['taxclass' => 'NonExistent', 'groups' => [['name' => 'VIP']]],
            ],
        ]);
    }

    // ── validateGroupName ─────────────────────────────────────────────────────

    public function testExecuteLogsErrorWhenGroupNameIsMissing(): void
    {
        $this->setupTaxClassFound(5);

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('mandatory'));

        $this->groupRepository->expects($this->never())->method('save');

        $this->component->execute([
            'customergroups' => [
                ['taxclass' => 'Retail', 'groups' => [[/* no name key */]]],
            ],
        ]);
    }

    public function testExecuteLogsErrorWhenGroupNameIsTooLong(): void
    {
        $this->setupTaxClassFound(5);

        $this->log->expects($this->once())
            ->method('logError')
            ->with($this->stringContains('too long'));

        $this->groupRepository->expects($this->never())->method('save');

        $this->component->execute([
            'customergroups' => [
                ['taxclass' => 'Retail', 'groups' => [['name' => str_repeat('A', 33)]]],
            ],
        ]);
    }

    // ── createCustomerGroup ───────────────────────────────────────────────────

    public function testExecuteSkipsCreateWhenGroupAlreadyExists(): void
    {
        $this->setupTaxClassFound(5);

        $searchResults = $this->createMock(SearchResults::class);
        $searchResults->method('getTotalCount')->willReturn(1);

        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')
            ->willReturn(new \Magento\Framework\Api\SearchCriteria());
        $this->groupRepository->method('getList')->willReturn($searchResults);

        $this->log->expects($this->once())
            ->method('logInfo')
            ->with($this->stringContains('already exists'));

        $this->groupRepository->expects($this->never())->method('save');

        $this->component->execute([
            'customergroups' => [
                ['taxclass' => 'Retail', 'groups' => [['name' => 'VIP']]],
            ],
        ]);
    }

    public function testExecuteCreatesNewCustomerGroupWhenNotFound(): void
    {
        $this->setupTaxClassFound(5);

        $searchResults = $this->createMock(SearchResults::class);
        $searchResults->method('getTotalCount')->willReturn(0);

        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')
            ->willReturn(new \Magento\Framework\Api\SearchCriteria());
        $this->groupRepository->method('getList')->willReturn($searchResults);

        $mockGroup = $this->createMock(Group::class);
        $mockGroup->expects($this->once())->method('setCode')->with('VIP');
        $mockGroup->expects($this->once())->method('setTaxClassId')->with(5);

        $this->groupDataFactory->method('create')->willReturn($mockGroup);
        $this->groupRepository->expects($this->once())->method('save')->with($mockGroup);

        $this->component->execute([
            'customergroups' => [
                ['taxclass' => 'Retail', 'groups' => [['name' => 'VIP']]],
            ],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function setupTaxClassFound(mixed $taxClassId): void
    {
        $taxClassItem = new class($taxClassId) {
            public function __construct(private readonly mixed $id) {}
            public function getId(): mixed { return $this->id; }
        };

        $collection = $this->createMock(TaxClassCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('getFirstItem')->willReturn($taxClassItem);

        $classModel = $this->createMock(ClassModel::class);
        $classModel->method('getCollection')->willReturn($collection);

        $this->classModelFactory->method('create')->willReturn($classModel);
    }
}
