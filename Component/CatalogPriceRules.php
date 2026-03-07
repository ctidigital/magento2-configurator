<?php
declare(strict_types=1);

/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <b.herba@ctidigital.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\CatalogPriceRules\CatalogPriceRulesProcessor;

class CatalogPriceRules implements ComponentInterface
{
    protected string $alias = 'catalog_price_rules';

    protected string $name = 'Catalog Price Rules';

    protected string $description = 'Component to manage Catalog Price Rules';

    public function __construct(
        private readonly CatalogPriceRulesProcessor $processor,
        private readonly LoggerInterface $log
    ) {}

    /**
     * Process the data and populate the Magento Database.
     */
    public function execute(mixed $data = null): void
    {
        $rules = $data['rules'] ?? [];
        $config = $data['config'] ?? [];

        $this->processor->setData($rules)
            ->setConfig($config)
            ->process();
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
