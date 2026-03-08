<?php

declare(strict_types=1);

/**
 * Standalone test stubs for all classes/interfaces the unit-test suite mocks.
 *
 * PURPOSE
 * -------
 * PHPUnit's getMockBuilder() calls ReflectionClass on every type it is asked to
 * mock. If the class does not exist the reflection fails — even though the test
 * itself never exercises the real implementation.
 *
 * These stubs declare the minimum structure needed for reflection to succeed.
 * Every stub uses a class_exists / interface_exists guard so that, when tests
 * run inside a full Magento installation, the real classes take precedence.
 *
 * TWO STUBS ARE FUNCTIONAL (not just shells):
 *   • Magento\Framework\Filesystem\DriverInterface / Driver\File
 *       SqlSplitProcessorTest instantiates FileDriver directly with `new FileDriver()`
 *       and needs real file-open/read/close behaviour to parse test.sql.
 *   • Magento\Framework\TestFramework\Unit\Helper\ObjectManager
 *       SqlSplitProcessorTest uses ObjectManager::getObject() to build the SUT
 *       with named constructor-argument injection via reflection.
 *
 * All other stubs are inert shells whose methods only throw LogicException if
 * called — a signal that the test forgot to mock them.
 */

// ═══════════════════════════════════════════════════════════════════════════════
// Magento global helpers
//
// The __() translation helper is a global function defined by the Magento
// framework bootstrap. Tests that call __('string') directly (rather than
// mocking it) need this stub.
// ═══════════════════════════════════════════════════════════════════════════════

namespace {
    if (!function_exists('__')) {
        // Returns string directly so ComponentException (extends RuntimeException)
        // can accept it without TypeError in PHP 8 strict-typing environments.
        function __(string $text, mixed ...$args): string
        {
            return $text;
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// PSR-7 HTTP Message interfaces
// ═══════════════════════════════════════════════════════════════════════════════

namespace Psr\Http\Message {

    if (!interface_exists(\Psr\Http\Message\MessageInterface::class)) {
        interface MessageInterface {}
    }

    if (!interface_exists(\Psr\Http\Message\RequestInterface::class)) {
        interface RequestInterface extends MessageInterface {}
    }

    if (!interface_exists(\Psr\Http\Message\ResponseInterface::class)) {
        interface ResponseInterface extends MessageInterface {
            public function getBody(): StreamInterface;
        }
    }

    if (!interface_exists(\Psr\Http\Message\StreamInterface::class)) {
        interface StreamInterface {
            public function __toString(): string;
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Symfony Console
// ═══════════════════════════════════════════════════════════════════════════════

namespace Symfony\Component\Console\Output {

    if (!interface_exists(\Symfony\Component\Console\Output\OutputInterface::class)) {
        interface OutputInterface
        {
            // Verbosity constants used by Magento's Logging class.
            const VERBOSITY_QUIET   = 16;
            const VERBOSITY_NORMAL  = 32;
            const VERBOSITY_VERBOSE = 64;
            const VERBOSITY_VERY_VERBOSE = 128;
            const VERBOSITY_DEBUG   = 256;
        }
    }

    if (!interface_exists(\Symfony\Component\Console\Output\ConsoleOutputInterface::class)) {
        interface ConsoleOutputInterface extends OutputInterface {}
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework — Phrase (value object used in exception constructors)
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework {

    if (!class_exists(\Magento\Framework\Phrase::class)) {
        class Phrase
        {
            public function __construct(
                private readonly string $text = '',
                private readonly array $arguments = []
            ) {}

            public function __toString(): string
            {
                return $this->text;
            }

            public function getText(): string
            {
                return $this->text;
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework\Exception hierarchy
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework\Exception {

    if (!class_exists(\Magento\Framework\Exception\LocalizedException::class)) {
        class LocalizedException extends \Exception
        {
            public function __construct(
                string|\Magento\Framework\Phrase $phrase,
                ?\Throwable $cause = null,
                int $code = 0
            ) {
                parent::__construct((string) $phrase, $code, $cause);
            }
        }
    }

    if (!class_exists(\Magento\Framework\Exception\CouldNotSaveException::class)) {
        class CouldNotSaveException extends LocalizedException {}
    }

    if (!class_exists(\Magento\Framework\Exception\NoSuchEntityException::class)) {
        class NoSuchEntityException extends LocalizedException {}
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework\Api — SearchCriteria / SearchCriteriaBuilder / SearchResults
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework\Api {

    if (!class_exists(\Magento\Framework\Api\SearchCriteria::class)) {
        class SearchCriteria {}
    }

    if (!class_exists(\Magento\Framework\Api\SearchCriteriaBuilder::class)) {
        class SearchCriteriaBuilder
        {
            public function addFilter(string $field, mixed $value, string $conditionType = 'eq'): static
            {
                return $this;
            }

            public function create(): SearchCriteria
            {
                return new SearchCriteria();
            }
        }
    }

    if (!class_exists(\Magento\Framework\Api\SearchResults::class)) {
        class SearchResults
        {
            public function getItems(): array
            {
                return [];
            }

            public function getTotalCount(): int
            {
                return 0;
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework\Config
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework\Config {

    if (!interface_exists(\Magento\Framework\Config\ScopeInterface::class)) {
        interface ScopeInterface {}
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework\App
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework\App {

    if (!class_exists(\Magento\Framework\App\Config::class)) {
        class Config {}
    }

    if (!class_exists(\Magento\Framework\App\ResourceConnection::class)) {
        class ResourceConnection
        {
            public function getConnection(string $resourceName = 'default'): \Magento\Framework\DB\Adapter\AdapterInterface
            {
                throw new \LogicException('Stub only — mock ResourceConnection::getConnection()');
            }
        }
    }

    if (!class_exists(\Magento\Framework\App\State::class)) {
        class State
        {
            public function __construct(?\Magento\Framework\Config\ScopeInterface $scope = null) {}
        }
    }

    if (!class_exists(\Magento\Framework\App\Area::class)) {
        class Area
        {
            public const AREA_ADMINHTML = 'adminhtml';
            public const AREA_FRONTEND  = 'frontend';
            public const AREA_GLOBAL    = 'global';
        }
    }
}

namespace Magento\Framework\App\Config {

    if (!class_exists(\Magento\Framework\App\Config\Initial::class)) {
        class Initial {}
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework\Encryption
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework\Encryption {

    if (!interface_exists(\Magento\Framework\Encryption\EncryptorInterface::class)) {
        interface EncryptorInterface {}
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework\Filesystem + DriverInterface (functional — used directly)
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework\Filesystem {

    if (!class_exists(\Magento\Framework\Filesystem::class)) {
        class Filesystem {}
    }

    /**
     * Functional stub: only the four methods SqlSplitProcessor actually calls.
     * Every other method throws so tests surface missing mocks clearly.
     */
    if (!interface_exists(\Magento\Framework\Filesystem\DriverInterface::class)) {
        interface DriverInterface
        {
            /** @return resource|false */
            public function fileOpen(string $path, string $mode): mixed;

            /** @param resource $file */
            public function endOfFile(mixed $file): bool;

            /** @param resource $file */
            public function fileReadLine(mixed $file, int $length, string $ending = "\n"): string|false;

            /** @param resource $file */
            public function fileClose(mixed $file): bool;

            public function fileGetContents(string $path, bool $useIncludePath = false, mixed $context = null): string;

            public function isExists(string $path): bool;
        }
    }
}

namespace Magento\Framework\Filesystem\Driver {

    /**
     * Functional stub: delegates to native PHP file I/O.
     * SqlSplitProcessorTest instantiates this directly via `new FileDriver()`.
     */
    if (!class_exists(\Magento\Framework\Filesystem\Driver\File::class)) {
        class File implements \Magento\Framework\Filesystem\DriverInterface
        {
            /** @return resource|false */
            public function fileOpen(string $path, string $mode): mixed
            {
                return fopen($path, $mode);
            }

            /** @param resource $file */
            public function endOfFile(mixed $file): bool
            {
                return feof($file);
            }

            /** @param resource $file */
            public function fileReadLine(mixed $file, int $length, string $ending = "\n"): string|false
            {
                return fgets($file, $length);
            }

            /** @param resource $file */
            public function fileClose(mixed $file): bool
            {
                return fclose($file);
            }

            public function fileGetContents(string $path, bool $useIncludePath = false, mixed $context = null): string
            {
                return (string) file_get_contents($path, $useIncludePath, $context ?: null);
            }

            public function isExists(string $path): bool
            {
                return file_exists($path);
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework\DB
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework\DB\Adapter {

    if (!interface_exists(\Magento\Framework\DB\Adapter\AdapterInterface::class)) {
        interface AdapterInterface {}
    }
}

namespace Magento\Framework\DB\Adapter\Pdo {

    if (!class_exists(\Magento\Framework\DB\Adapter\Pdo\Mysql::class)) {
        class Mysql implements \Magento\Framework\DB\Adapter\AdapterInterface
        {
            public function beginTransaction(): void
            {
                throw new \LogicException('Stub only — mock Mysql::beginTransaction()');
            }

            public function query(string $sql): void
            {
                throw new \LogicException('Stub only — mock Mysql::query()');
            }

            public function rollBack(): void
            {
                throw new \LogicException('Stub only — mock Mysql::rollBack()');
            }

            public function commit(): void
            {
                throw new \LogicException('Stub only — mock Mysql::commit()');
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework\TestFramework\Unit\Helper\ObjectManager (functional stub)
//
// SqlSplitProcessorTest calls:
//   $this->objectManager->getObject(SqlSplitProcessor::class, ['log'=>…, …])
//
// The real ObjectManager resolves constructor arguments by parameter name via
// reflection. This stub replicates that behaviour.
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework\TestFramework\Unit\Helper {

    if (!class_exists(\Magento\Framework\TestFramework\Unit\Helper\ObjectManager::class)) {
        class ObjectManager
        {
            public function __construct(private readonly object $testCase) {}

            /**
             * Instantiate $className, injecting $arguments by constructor parameter name.
             * Parameters not present in $arguments are set to null (or their default value).
             */
            public function getObject(string $className, array $arguments = []): object
            {
                $reflection = new \ReflectionClass($className);
                $constructor = $reflection->getConstructor();

                if ($constructor === null) {
                    return $reflection->newInstance();
                }

                $params = [];
                foreach ($constructor->getParameters() as $param) {
                    $name = $param->getName();
                    if (array_key_exists($name, $arguments)) {
                        $params[] = $arguments[$name];
                    } elseif ($param->isOptional()) {
                        $params[] = $param->getDefaultValue();
                    } else {
                        $params[] = null;
                    }
                }

                return $reflection->newInstanceArgs($params);
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\CatalogRule
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\CatalogRule\Api {

    if (!interface_exists(\Magento\CatalogRule\Api\CatalogRuleRepositoryInterface::class)) {
        interface CatalogRuleRepositoryInterface
        {
            public function save(\Magento\CatalogRule\Api\Data\RuleInterface $rule): \Magento\CatalogRule\Api\Data\RuleInterface;
            public function get(int $ruleId): \Magento\CatalogRule\Api\Data\RuleInterface;
            public function delete(\Magento\CatalogRule\Api\Data\RuleInterface $rule): bool;
            public function deleteById(int $ruleId): bool;
        }
    }
}

namespace Magento\CatalogRule\Api\Data {

    if (!interface_exists(\Magento\CatalogRule\Api\Data\RuleInterface::class)) {
        interface RuleInterface {}
    }

    if (!class_exists(\Magento\CatalogRule\Api\Data\RuleInterfaceFactory::class)) {
        class RuleInterfaceFactory
        {
            public function create(array $data = []): RuleInterface
            {
                throw new \RuntimeException('Stub only — mock RuleInterfaceFactory::create()');
            }
        }
    }
}

namespace Magento\CatalogRule\Model {

    if (!class_exists(\Magento\CatalogRule\Model\Rule::class)) {
        // Rule implements RuleInterface so that a Rule mock can be returned from
        // RuleInterfaceFactory::create() without triggering IncompatibleReturnValueException.
        class Rule implements \Magento\CatalogRule\Api\Data\RuleInterface
        {
            public function getId(): mixed
            {
                return null;
            }

            public function getData(?string $key = null): mixed
            {
                return null;
            }

            public function setData(mixed $key, mixed $value = null): static
            {
                return $this;
            }

            public function loadPost(mixed $data): static
            {
                return $this;
            }

            public function getCollection(): \Magento\CatalogRule\Model\ResourceModel\Rule\Collection
            {
                throw new \LogicException('Stub only — mock Rule::getCollection()');
            }
        }
    }
}

namespace Magento\CatalogRule\Model\ResourceModel\Rule {

    if (!class_exists(\Magento\CatalogRule\Model\ResourceModel\Rule\Collection::class)) {
        class Collection
        {
            public function addFieldToFilter(string $field, mixed $condition = null): static
            {
                return $this;
            }

            public function getSize(): int
            {
                return 0;
            }

            public function getFirstItem(): \Magento\CatalogRule\Model\Rule
            {
                return new \Magento\CatalogRule\Model\Rule();
            }
        }
    }
}

namespace Magento\CatalogRule\Model\Rule {

    if (!class_exists(\Magento\CatalogRule\Model\Rule\Job::class)) {
        class Job
        {
            public function applyAll(): void
            {
                throw new \LogicException('Stub only — mock Job::applyAll()');
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Catalog
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Catalog\Api\Data {

    if (!interface_exists(\Magento\Catalog\Api\Data\ProductAttributeInterface::class)) {
        // Minimal interface — methods added are those called by production code.
        interface ProductAttributeInterface
        {
            public function getAttributeCode(): string;
            public function getFrontendInput(): string;
            public function getOptions(): array;
            public function getBackendModel(): ?string;
            public function getIsUserDefined(): bool;
            public function getFrontend(): static;
            public function getAttributeId(): mixed;
        }
    }
}

namespace Magento\Catalog\Api {

    if (!interface_exists(\Magento\Catalog\Api\ProductAttributeRepositoryInterface::class)) {
        interface ProductAttributeRepositoryInterface
        {
            public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria): \Magento\Framework\Api\SearchResults;
            public function get(string $attributeCode): \Magento\Catalog\Model\ResourceModel\Eav\Attribute;
            public function save(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute): \Magento\Catalog\Model\ResourceModel\Eav\Attribute;
            public function delete(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute): bool;
            public function deleteById(string $attributeCode): bool;
            public function getCustomAttributesMetadata(?string $dataObjectClassName = null): array;
        }
    }
}

namespace Magento\Catalog\Model {

    if (!class_exists(\Magento\Catalog\Model\Product::class)) {
        class Product
        {
            public function hasData(string $key = ''): mixed
            {
                return false;
            }

            public function getSku(): ?string
            {
                return null;
            }

            // Return type is mixed (not int|false) to allow willReturnSelf() in tests.
            public function getIdBySku(string $sku): mixed
            {
                return false;
            }

            public function load(mixed $id, mixed $field = null): static
            {
                return $this;
            }

            public function getId(): mixed
            {
                return null;
            }

            public function getResource(): static
            {
                return $this;
            }
        }
    }

    if (!class_exists(\Magento\Catalog\Model\ProductFactory::class)) {
        class ProductFactory
        {
            public function create(array $data = []): Product
            {
                return new Product();
            }
        }
    }
}

namespace Magento\Catalog\Model\ResourceModel\Eav {

    if (!class_exists(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class)) {
        class Attribute implements \Magento\Catalog\Api\Data\ProductAttributeInterface
        {
            public function getAttributeCode(): string
            {
                return '';
            }

            public function getFrontendInput(): string
            {
                return '';
            }

            public function getOptions(): array
            {
                return [];
            }

            public function getBackendModel(): ?string
            {
                return null;
            }

            public function getIsUserDefined(): bool
            {
                return false;
            }

            public function getFrontend(): static
            {
                return $this;
            }

            public function getAttributeId(): mixed
            {
                return null;
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Config
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Config\Model\ResourceModel {

    if (!class_exists(\Magento\Config\Model\ResourceModel\Config::class)) {
        class Config {}
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Customer
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Customer\Api {

    if (!interface_exists(\Magento\Customer\Api\GroupManagementInterface::class)) {
        interface GroupManagementInterface
        {
            public function isReadOnly(int $id): bool;
            public function getNotLoggedInGroup(): \Magento\Customer\Model\Data\Group;
            public function getLoggedInGroups(): array;
            public function getAllCustomersGroup(): \Magento\Customer\Model\Data\Group;
            public function getDefaultGroup(?int $storeId = null): \Magento\Customer\Model\Data\Group;
        }
    }

    if (!interface_exists(\Magento\Customer\Api\GroupRepositoryInterface::class)) {
        interface GroupRepositoryInterface
        {
            public function save(\Magento\Customer\Model\Data\Group $group): \Magento\Customer\Model\Data\Group;
            public function getById(int $id): \Magento\Customer\Model\Data\Group;
            public function delete(\Magento\Customer\Model\Data\Group $group): bool;
            public function deleteById(int $id): bool;
            public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria): \Magento\Framework\Api\SearchResults;
        }
    }
}

namespace Magento\Customer\Model\Data {

    if (!class_exists(\Magento\Customer\Model\Data\Group::class)) {
        class Group
        {
            public function getId(): mixed
            {
                return null;
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Eav
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Eav\Api {

    if (!interface_exists(\Magento\Eav\Api\AttributeOptionManagementInterface::class)) {
        interface AttributeOptionManagementInterface {}
    }
}

namespace Magento\Eav\Api\Data {

    if (!class_exists(\Magento\Eav\Api\Data\AttributeOptionInterfaceFactory::class)) {
        class AttributeOptionInterfaceFactory
        {
            public function create(array $data = []): object
            {
                throw new \LogicException('Stub only — mock AttributeOptionInterfaceFactory::create()');
            }
        }
    }

    if (!class_exists(\Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory::class)) {
        class AttributeOptionLabelInterfaceFactory
        {
            public function create(array $data = []): object
            {
                throw new \LogicException('Stub only — mock AttributeOptionLabelInterfaceFactory::create()');
            }
        }
    }
}

namespace Magento\Eav\Model\Entity {

    if (!class_exists(\Magento\Eav\Model\Entity\Attribute::class)) {
        // Methods declared here must match those used in onlyMethods() calls in tests.
        class Attribute
        {
            public function getFrontend(): static
            {
                return $this;
            }

            public function getAttributeCode(): string
            {
                return '';
            }
        }
    }
}

namespace Magento\Eav\Model\Entity\Attribute {

    if (!class_exists(\Magento\Eav\Model\Entity\Attribute\Option::class)) {
        class Option
        {
            public function getLabel(): string
            {
                return '';
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Indexer
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Indexer\Model {

    if (!class_exists(\Magento\Indexer\Model\Indexer::class)) {
        class Indexer
        {
            public function load(string $indexerId): static
            {
                throw new \LogicException('Stub only — mock Indexer::load()');
            }

            public function reindexAll(): void
            {
                throw new \LogicException('Stub only — mock Indexer::reindexAll()');
            }
        }
    }

    if (!class_exists(\Magento\Indexer\Model\IndexerFactory::class)) {
        class IndexerFactory
        {
            public function create(array $data = []): Indexer
            {
                throw new \LogicException('Stub only — mock IndexerFactory::create()');
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Review
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Review\Model {

    if (!class_exists(\Magento\Review\Model\Rating::class)) {
        class Rating
        {
            public function load(mixed $id, mixed $field = null): static
            {
                return $this;
            }

            public function getId(): mixed
            {
                return null;
            }
        }
    }

    if (!class_exists(\Magento\Review\Model\RatingFactory::class)) {
        class RatingFactory
        {
            public function create(array $data = []): Rating
            {
                return new Rating();
            }
        }
    }
}

namespace Magento\Review\Model\Rating {

    if (!class_exists(\Magento\Review\Model\Rating\EntityFactory::class)) {
        class EntityFactory
        {
            public function create(array $data = []): object
            {
                throw new \LogicException('Stub only — mock Rating\\EntityFactory::create()');
            }
        }
    }

    if (!class_exists(\Magento\Review\Model\Rating\OptionFactory::class)) {
        class OptionFactory
        {
            public function create(array $data = []): object
            {
                throw new \LogicException('Stub only — mock Rating\\OptionFactory::create()');
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Tax — Service Contract API (repositories, data interfaces, factories)
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Tax\Api\Data {

    if (!interface_exists(\Magento\Tax\Api\Data\TaxRuleInterface::class)) {
        interface TaxRuleInterface
        {
            public function setCode(string $code): static;
            public function getCode(): string;
            public function setPriority(int $priority): static;
            public function setPosition(int $position): static;
            public function setCalculateSubtotal(bool $calculateSubtotal): static;
            public function setTaxRateIds(array $taxRateIds): static;
            public function setCustomerTaxClassIds(array $customerTaxClassIds): static;
            public function setProductTaxClassIds(array $productTaxClassIds): static;
        }
    }

    if (!class_exists(\Magento\Tax\Api\Data\TaxRuleInterfaceFactory::class)) {
        class TaxRuleInterfaceFactory
        {
            public function create(array $data = []): TaxRuleInterface
            {
                throw new \LogicException('Stub only — mock TaxRuleInterfaceFactory::create()');
            }
        }
    }

    if (!interface_exists(\Magento\Tax\Api\Data\TaxRateInterface::class)) {
        interface TaxRateInterface
        {
            public function getId(): mixed;
            public function getCode(): string;
        }
    }

    if (!interface_exists(\Magento\Tax\Api\Data\TaxClassInterface::class)) {
        interface TaxClassInterface
        {
            public function getClassId(): int;
            public function setClassName(string $className): static;
            public function setClassType(string $classType): static;
        }
    }

    if (!class_exists(\Magento\Tax\Api\Data\TaxClassInterfaceFactory::class)) {
        class TaxClassInterfaceFactory
        {
            public function create(array $data = []): TaxClassInterface
            {
                throw new \LogicException('Stub only — mock TaxClassInterfaceFactory::create()');
            }
        }
    }
}

namespace Magento\Tax\Api {

    if (!interface_exists(\Magento\Tax\Api\TaxRuleRepositoryInterface::class)) {
        interface TaxRuleRepositoryInterface
        {
            public function save(\Magento\Tax\Api\Data\TaxRuleInterface $rule): \Magento\Tax\Api\Data\TaxRuleInterface;
            public function get(int $ruleId): \Magento\Tax\Api\Data\TaxRuleInterface;
            public function delete(\Magento\Tax\Api\Data\TaxRuleInterface $rule): bool;
            public function deleteById(int $ruleId): bool;
            public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria): \Magento\Framework\Api\SearchResults;
        }
    }

    if (!interface_exists(\Magento\Tax\Api\TaxRateRepositoryInterface::class)) {
        interface TaxRateRepositoryInterface
        {
            public function save(\Magento\Tax\Api\Data\TaxRateInterface $rate): \Magento\Tax\Api\Data\TaxRateInterface;
            public function get(int $rateId): \Magento\Tax\Api\Data\TaxRateInterface;
            public function delete(\Magento\Tax\Api\Data\TaxRateInterface $rate): bool;
            public function deleteById(int $rateId): bool;
            public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria): \Magento\Framework\Api\SearchResults;
        }
    }

    if (!interface_exists(\Magento\Tax\Api\TaxClassRepositoryInterface::class)) {
        interface TaxClassRepositoryInterface
        {
            public function save(\Magento\Tax\Api\Data\TaxClassInterface $taxClass): \Magento\Tax\Api\Data\TaxClassInterface;
            public function get(int $taxClassId): \Magento\Tax\Api\Data\TaxClassInterface;
            public function delete(\Magento\Tax\Api\Data\TaxClassInterface $taxClass): bool;
            public function deleteById(int $taxClassId): bool;
            public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria): \Magento\Framework\Api\SearchResults;
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework\Event
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework\Event {

    if (!interface_exists(\Magento\Framework\Event\ManagerInterface::class)) {
        interface ManagerInterface
        {
            public function dispatch(string $eventName, array $data = []): void;
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Store
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Store\Api {

    if (!interface_exists(\Magento\Store\Api\StoreRepositoryInterface::class)) {
        interface StoreRepositoryInterface {}
    }
}

namespace Magento\Store\Model {

    if (!class_exists(\Magento\Store\Model\Website::class)) {
        class Website
        {
            public function load(mixed $id, mixed $field = null): static { return $this; }
            public function getId(): mixed { return null; }
            public function getData(?string $key = null): mixed { return []; }
            public function setData(mixed $key, mixed $value = null): static { return $this; }
            public function setCode(string $code): static { return $this; }
            public function getResource(): static { return $this; }
            public function save(mixed $model): void {}
        }
    }

    if (!class_exists(\Magento\Store\Model\Store::class)) {
        class Store
        {
            public function load(mixed $id, mixed $field = null): static { return $this; }
            public function getId(): mixed { return null; }
            public function getData(?string $key = null): mixed { return []; }
            public function setData(mixed $key, mixed $value = null): static { return $this; }
            public function setCode(string $code): static { return $this; }
            public function setGroup(mixed $group): static { return $this; }
            public function getStoreGroupId(): mixed { return null; }
            public function getCode(): string { return ''; }
            public function getResource(): static { return $this; }
            public function save(mixed $model): void {}
        }
    }

    if (!class_exists(\Magento\Store\Model\Group::class)) {
        class Group
        {
            public function load(mixed $id, mixed $field = null): static { return $this; }
            public function getId(): mixed { return null; }
            public function getName(): string { return ''; }
            public function getData(?string $key = null): mixed { return []; }
            public function setData(mixed $key, mixed $value = null): static { return $this; }
            public function setWebsite(mixed $website): static { return $this; }
            public function getDefaultStoreId(): mixed { return null; }
            public function setDefaultStoreId(mixed $id): static { return $this; }
            public function getResource(): static { return $this; }
            public function save(mixed $model): void {}
        }
    }

    if (!class_exists(\Magento\Store\Model\StoreFactory::class)) {
        class StoreFactory
        {
            public function create(array $data = []): Store
            {
                throw new \LogicException('Stub only — mock StoreFactory::create()');
            }
        }
    }

    if (!class_exists(\Magento\Store\Model\WebsiteFactory::class)) {
        class WebsiteFactory
        {
            public function create(array $data = []): Website
            {
                throw new \LogicException('Stub only — mock WebsiteFactory::create()');
            }
        }
    }

    if (!class_exists(\Magento\Store\Model\GroupFactory::class)) {
        class GroupFactory
        {
            public function create(array $data = []): Group
            {
                throw new \LogicException('Stub only — mock GroupFactory::create()');
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Theme
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Theme\Model {

    if (!class_exists(\Magento\Theme\Model\Theme::class)) {
        class Theme {}
    }
}

namespace Magento\Theme\Model\ResourceModel\Theme {

    if (!class_exists(\Magento\Theme\Model\ResourceModel\Theme\Collection::class)) {
        class Collection
        {
            public function getThemeByFullPath(string $fullPath): \Magento\Theme\Model\Theme
            {
                throw new \LogicException('Stub only — mock Collection::getThemeByFullPath()');
            }
        }
    }

    if (!class_exists(\Magento\Theme\Model\ResourceModel\Theme\CollectionFactory::class)) {
        class CollectionFactory
        {
            public function create(array $data = []): Collection
            {
                return new Collection();
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Framework\Webapi
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Framework\Webapi\Rest {

    if (!class_exists(\Magento\Framework\Webapi\Rest\Request::class)) {
        class Request
        {
            // HTTP method constants used by Image::downloadFile().
            const HTTP_METHOD_GET    = 'GET';
            const HTTP_METHOD_POST   = 'POST';
            const HTTP_METHOD_PUT    = 'PUT';
            const HTTP_METHOD_PATCH  = 'PATCH';
            const HTTP_METHOD_DELETE = 'DELETE';
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// FireGento\FastSimpleImport
// ═══════════════════════════════════════════════════════════════════════════════

namespace FireGento\FastSimpleImport\Model {

    if (!class_exists(\FireGento\FastSimpleImport\Model\ImporterFactory::class)) {
        class ImporterFactory
        {
            public function create(array $data = []): object
            {
                throw new \LogicException('Stub only — mock ImporterFactory::create()');
            }
        }
    }

    if (!class_exists(\FireGento\FastSimpleImport\Model\Config::class)) {
        class Config {}
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// GuzzleHttp
// ═══════════════════════════════════════════════════════════════════════════════

namespace GuzzleHttp {

    if (!class_exists(\GuzzleHttp\Client::class)) {
        class Client
        {
            public function __construct(array $config = []) {}

            public function request(
                string $method,
                mixed $uri = '',
                array $options = []
            ): \Psr\Http\Message\ResponseInterface {
                throw new \LogicException('Stub only — mock Client::request()');
            }
        }
    }

    if (!class_exists(\GuzzleHttp\ClientFactory::class)) {
        class ClientFactory
        {
            public function create(array $data = []): Client
            {
                throw new \LogicException('Stub only — mock ClientFactory::create()');
            }
        }
    }
}

namespace GuzzleHttp\Exception {

    // GuzzleException is the marker interface that Image::downloadFile() catches.
    if (!interface_exists(\GuzzleHttp\Exception\GuzzleException::class)) {
        interface GuzzleException extends \Throwable {}
    }

    if (!class_exists(\GuzzleHttp\Exception\TransferException::class)) {
        class TransferException extends \RuntimeException implements GuzzleException {}
    }

    if (!class_exists(\GuzzleHttp\Exception\RequestException::class)) {
        class RequestException extends TransferException
        {
            public function __construct(
                string $message,
                \Psr\Http\Message\RequestInterface $request,
                ?\Psr\Http\Message\ResponseInterface $response = null,
                ?\Throwable $previous = null
            ) {
                parent::__construct($message, 0, $previous);
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Customer\Api\Data — GroupInterfaceFactory
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Customer\Api\Data {

    if (!class_exists(\Magento\Customer\Api\Data\GroupInterfaceFactory::class)) {
        class GroupInterfaceFactory
        {
            public function create(array $data = []): \Magento\Customer\Model\Data\Group
            {
                throw new \LogicException('Stub only — mock GroupInterfaceFactory::create()');
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Tax\Model — ClassModel + ClassModelFactory (collection-based lookup)
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Tax\Model\ResourceModel\TaxClass {

    if (!class_exists(\Magento\Tax\Model\ResourceModel\TaxClass\Collection::class)) {
        class Collection
        {
            public function addFieldToFilter(string $field, mixed $value): static
            {
                return $this;
            }

            public function getFirstItem(): object
            {
                return new class {
                    public function getId(): mixed { return null; }
                };
            }
        }
    }
}

namespace Magento\Tax\Model {

    if (!class_exists(\Magento\Tax\Model\ClassModel::class)) {
        class ClassModel
        {
            public function getCollection(): \Magento\Tax\Model\ResourceModel\TaxClass\Collection
            {
                throw new \LogicException('Stub only — mock ClassModel::getCollection()');
            }
        }
    }

    if (!class_exists(\Magento\Tax\Model\ClassModelFactory::class)) {
        class ClassModelFactory
        {
            public function create(array $data = []): ClassModel
            {
                throw new \LogicException('Stub only — mock ClassModelFactory::create()');
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Cms — Page, PageRepository, PageInterfaceFactory
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Cms\Api\Data {

    if (!interface_exists(\Magento\Cms\Api\Data\PageInterface::class)) {
        interface PageInterface {}
    }

    if (!class_exists(\Magento\Cms\Api\Data\PageInterfaceFactory::class)) {
        class PageInterfaceFactory
        {
            public function create(array $data = []): \Magento\Cms\Model\Page
            {
                throw new \LogicException('Stub only — mock PageInterfaceFactory::create()');
            }
        }
    }
}

namespace Magento\Cms\Api {

    if (!interface_exists(\Magento\Cms\Api\PageRepositoryInterface::class)) {
        interface PageRepositoryInterface
        {
            public function getById(int $pageId): \Magento\Cms\Api\Data\PageInterface;
            public function save(\Magento\Cms\Api\Data\PageInterface $page): \Magento\Cms\Api\Data\PageInterface;
            public function delete(\Magento\Cms\Api\Data\PageInterface $page): bool;
            public function deleteById(int $pageId): bool;
        }
    }
}

namespace Magento\Cms\Model {

    if (!class_exists(\Magento\Cms\Model\Page::class)) {
        class Page implements \Magento\Cms\Api\Data\PageInterface
        {
            private array $_data = [];

            /** Returns 0/false when the page identifier does not exist in the given store. */
            public function checkIdentifier(string $identifier, int $storeId): int|false
            {
                return false;
            }

            public function getId(): mixed
            {
                return $this->_data['id'] ?? null;
            }

            public function setIdentifier(string $identifier): static
            {
                $this->_data['identifier'] = $identifier;
                return $this;
            }

            public function getData(string $key = ''): mixed
            {
                return $key !== '' ? ($this->_data[$key] ?? null) : $this->_data;
            }

            public function setData(string|array $key, mixed $value = null): static
            {
                if (is_string($key)) {
                    $this->_data[$key] = $value;
                }
                return $this;
            }

            public function setStores(array $stores): static
            {
                $this->_data['stores'] = $stores;
                return $this;
            }

            public function unsetData(string $key): static
            {
                unset($this->_data[$key]);
                return $this;
            }

            public function hasDataChanges(): bool
            {
                return false;
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Magento\Integration — IntegrationService, AuthorizationService, Token
// ═══════════════════════════════════════════════════════════════════════════════

namespace Magento\Integration\Api\Data {

    if (!interface_exists(\Magento\Integration\Api\Data\IntegrationInterface::class)) {
        interface IntegrationInterface
        {
            public function getId(): mixed;
            public function getName(): string;
            public function getConsumerId(): mixed;
        }
    }
}

namespace Magento\Integration\Api {

    if (!interface_exists(\Magento\Integration\Api\IntegrationServiceInterface::class)) {
        interface IntegrationServiceInterface
        {
            public function findByName(string $name): \Magento\Integration\Api\Data\IntegrationInterface;
            public function create(array $integrationData): \Magento\Integration\Api\Data\IntegrationInterface;
        }
    }
}

namespace Magento\Integration\Model {

    if (!class_exists(\Magento\Integration\Model\AuthorizationService::class)) {
        class AuthorizationService
        {
            public function grantPermissions(mixed $integrationId, ?array $resources): void
            {
                throw new \LogicException('Stub only — mock AuthorizationService::grantPermissions()');
            }
        }
    }
}

namespace Magento\Integration\Model\Oauth {

    if (!class_exists(\Magento\Integration\Model\Oauth\Token::class)) {
        class Token
        {
            public function createVerifierToken(mixed $consumerId): static
            {
                return $this;
            }

            public function setType(string $type): static
            {
                return $this;
            }

            public function save(): static
            {
                return $this;
            }
        }
    }

    if (!class_exists(\Magento\Integration\Model\Oauth\TokenFactory::class)) {
        class TokenFactory
        {
            public function create(array $data = []): Token
            {
                throw new \LogicException('Stub only — mock TokenFactory::create()');
            }
        }
    }
}
