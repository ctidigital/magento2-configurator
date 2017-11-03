<?php

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use CtiDigital\Configurator\Model\Exception\ComponentException;

class ProductLinks extends YamlComponentAbstract
{

    protected $alias = 'product_links';
    protected $name = 'Product Links';
    protected $description = 'Component to create and maintain product links (related/up-sells/cross-sells)';


    // @var Magento\Catalog\Api\Data\ProductLinkInterfaceFactory $productLinkFactory
    protected $productLinkFactory;

    protected $productRepository;

    protected $allowedLinks = ['relation', 'up_sell', 'cross_sell'];
    protected $linkTypeMap = ['relation' => 'related', 'up_sell' => 'upsell', 'cross_sell' => 'crosssell'];

    public function __construct(
        LoggerInterface $log,
        ObjectManagerInterface $objectManager,
        ProductRepositoryInterface $productRepository,
        ProductLinkInterfaceFactory $productLinkFactory
    ) {
        parent::__construct($log, $objectManager);
        $this->productRepository = $productRepository;
        $this->productLinkFactory = $productLinkFactory;
    }

    /**
     * Process the data by splitting up the different link types.
     *
     * @param $data
     */
    public function processData($data = null)
    {
        try {

            // Loop through all the product link types - if there are multiple link types in the yaml file
            foreach ($data as $linkType => $skus) {

                // Validate the link type to see if it is allowed
                if (!in_array($linkType, $this->allowedLinks)) {
                    throw new ComponentException(sprintf('Link type %s is not supported', $linkType));
                }

                // Process creating the links
                $this->processSkus($skus, $linkType);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        } catch (\Exception $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * Process an array of products that require products linking to them
     *
     * @param array $data
     * @param $linkType
     */
    private function processSkus(array $data, $linkType)
    {
        try {

            // Loop through the SKUs in the link type
            foreach ($data as $sku => $linkSkus) {

                // Check if the product exists
                if (!$this->doesProductExist($sku)) {
                    throw new ComponentException(sprintf('SKU (%s) for products to link to is not found', $sku));
                }
                $this->log->logInfo(sprintf('Creating product links for %s', $sku));

                // Process the links for that product
                $this->processLinks($sku, $linkSkus, $linkType);
            }
        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage());
        } catch (\Exception $e) {
            $this->log->logError($e->getMessage());
        }
    }

    /**
     * Process all the SKUs that need to be linked to a particular product (SKU)
     *
     * @param $sku
     * @param $linkSkus
     * @param $linkType
     */
    private function processLinks($sku, $linkSkus, $linkType)
    {
        try {

            $productLinks = array();

            // Loop through all the products that require linking to a product
            foreach ($linkSkus as $position => $linkSku) {

                // Check if the product exists
                if (!$this->doesProductExist($linkSku)) {
                    throw new ComponentException(sprintf('SKU (%s) to link does not exist', $linkSku));
                }

                // Create an array of product link objects
                $productLinks[] = $this->productLinkFactory->create()->setSku($sku)
                    ->setLinkedProductSku($linkSku)
                    ->setLinkType($this->linkTypeMap[$linkType])
                    ->setPosition($position * 10);
                $this->log->logInfo($linkSku, 1);
            }

            // Save product links onto the main product
            $this->productRepository->get($sku)->setProductLinks($productLinks)->save();
            $this->log->logComment(sprintf('Saved product links for %s', $sku), 1);

        } catch (ComponentException $e) {
            $this->log->logError($e->getMessage(), 1);
        } catch (\Exception $e) {
            $this->log->logError($e->getMessage(), 1);
        }
    }

    /**
     * Check if the product exists function
     *
     * @param string $sku
     * @return bool
     * @todo find an efficient way to check if the product exists.
     */
    private function doesProductExist($sku)
    {
        if ($this->productRepository->get($sku)->getId()) {
            return true;
        }
        return false;
    }
}
