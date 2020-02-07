<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <b.herba@ctidigital.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\CatalogPriceRules\CatalogPriceRulesProcessor;
use Magento\CatalogRule\Api\Data\RuleInterfaceFactory;

class CatalogPriceRules implements ComponentInterface
{
    /**
     * @var string
     */
    protected $alias = 'catalog_price_rules';

    /**
     * @var string
     */
    protected $name = 'Catalog Price Rules';

    /**
     * @var string
     */
    protected $description = 'Component to manage Catalog Price Rules';

    /**
     * @var CatalogPriceRulesProcessor
     */
    private $processor;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * CatalogPriceRules constructor.
     *
     * @param LoggerInterface $log
     * @param CatalogPriceRulesProcessor $processor
     */
    public function __construct(
        CatalogPriceRulesProcessor $processor,
        LoggerInterface $log
    ) {
        $this->processor = $processor;
        $this->log = $log;
    }

    /**
     * This method should be used to process the data and populate the Magento Database.
     *
     * @param $data
     *
     * @return void
     */
    public function execute($data = null)
    {
        $rules = $data['rules'] ?: [];
        $config = $data['config'] ?: [];

        $this->processor->setData($rules)
            ->setConfig($config)
            ->process();
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
