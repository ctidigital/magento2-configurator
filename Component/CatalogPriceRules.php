<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <b.herba@ctidigital.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentProcessorInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\CatalogRule\Api\Data\RuleInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class CatalogPriceRules
 */
class CatalogPriceRules extends YamlComponentAbstract
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
     * @var ComponentProcessorInterface
     */
    private $processor;

    /**
     * CatalogPriceRules constructor.
     *
     * @param LoggerInterface $log
     * @param ObjectManagerInterface $objectManager
     * @param ComponentProcessorInterface $processor
     */
    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        ComponentProcessorInterface $processor
    ) {
        parent::__construct($log, $objectManager);

        $this->processor = $processor;
    }

    /**
     * This method should be used to process the data and populate the Magento Database.
     *
     * @param $data
     *
     * @return void
     */
    protected function processData($data = null)
    {
        $rules = $data['rules'] ?: [];
        $config = $data['config'] ?: [];

        $this->processor->setData($rules)
            ->setConfig($config)
            ->process();
    }
}
