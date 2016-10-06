<?php

namespace CtiDigital\Configurator\Helper;

use CtiDigital\Configurator\Model\Exception\ComponentException;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Component
 * Helper for Configurator Components
 *
 * @package CtiDigital\Configurator\Helper
 */
class Component extends AbstractHelper
{

    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * Component constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Context $context, StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Get a Store by Code
     *
     * @param $code
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws ComponentException
     */
    public function getStoreByCode($code)
    {
        // Load the store object
        $store = $this->storeManager->getStore($code);

        // Check if we get back a store ID.
        if (!$store->getId()) {

            // If not, stop the process by throwing an exception
            throw new ComponentException(sprintf("No store with code '%s' found", $code));
        }

        return $store;
    }

}