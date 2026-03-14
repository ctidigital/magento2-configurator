<?php

declare(strict_types=1);

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Cms\Model\GetBlockByIdentifier;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Resolves a CMS block value (numeric ID or string identifier) to a block entity ID.
 */
class CmsBlockResolver
{
    public function __construct(
        private readonly GetBlockByIdentifier $blockByIdentifier,
        private readonly LoggerInterface $log
    ) {
    }

    /**
     * Returns the block entity ID for the given value.
     *
     * Numeric values are cast and returned directly. String values are treated
     * as block identifiers and looked up via the CMS block service.
     */
    public function resolve(mixed $value, int $storeId = 0): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        try {
            $block = $this->blockByIdentifier->execute((string) $value, $storeId);
            return (int) $block->getId();
        } catch (NoSuchEntityException $e) {
            $this->log->logError(
                sprintf('Failed to find CMS block with identifier "%s": %s', $value, $e->getMessage())
            );
            return 0;
        }
    }
}
