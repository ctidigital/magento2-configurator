<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Component;

use CtiDigital\Configurator\Api\ComponentInterface;
use CtiDigital\Configurator\Api\LoggerInterface;
use Magento\Review\Model\Rating;
use Magento\Review\Model\RatingFactory;
use Magento\Review\Model\Rating\Entity;
use Magento\Review\Model\Rating\EntityFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Review\Model\Rating\Option;
use Magento\Review\Model\Rating\OptionFactory;

/**
 * @SuppressWarnings("CouplingBetweenObjects")
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class ReviewRating implements ComponentInterface
{
    const MAX_NUM_RATINGS = 5;

    protected string $alias = 'review_rating';

    protected string $name = 'Review Rating';

    protected string $description = 'Component to create review ratings';

    protected mixed $entityId = null;

    public function __construct(
        protected readonly RatingFactory $ratingFactory,
        protected readonly StoreRepositoryInterface $storeRepository,
        protected readonly OptionFactory $optionFactory,
        protected readonly EntityFactory $entityFactory,
        private readonly LoggerInterface $log
    ) {
    }

    public function execute(mixed $data = null): void
    {
        $reviewRatings = $this->getReviewRatings($data);

        foreach ($reviewRatings as $code => $reviewRating) {
            try {
                $ratingModel = $this->getReviewRating($code);
                $ratingModel = $this->updateOrCreateRating($ratingModel, $code, $reviewRating);
                $ratingModel->save();
                $this->setOptions($ratingModel);
                $this->log->logInfo(__('Updated review rating "%1"', $code));
            } catch (\Exception $e) {
                $this->log->logError(
                    sprintf(
                        'Failed updating review rating "%s". Error message: %s',
                        $code,
                        $e->getMessage()
                    )
                );
            }
        }
    }

    /**
     * Get the review criteria
     */
    public function getReviewRatings(mixed $data): array
    {
        if (isset($data['review_rating'])) {
            return $data['review_rating'];
        }
        return [];
    }

    public function getReviewRating(mixed $reviewRatingCode): Rating
    {
        $rating = $this->ratingFactory->create();
        $rating->load($reviewRatingCode, 'rating_code');
        return $rating;
    }

    public function updateOrCreateRating(Rating $rating, mixed $ratingCode, mixed $ratingData): Rating
    {
        $rating->setRatingCode($ratingCode);
        $reviewEntityId = $this->getReviewEntityId();
        $rating->setEntityId($reviewEntityId);
        $isActive = 0;
        if (isset($ratingData['is_active'])) {
            $isActive = $ratingData['is_active'];
        }
        $rating->setData('is_active', $isActive);

        $position = 0;
        if (isset($ratingData['position'])) {
            $position = $ratingData['position'];
        }
        $rating->setData('position', $position);

        $stores = [];
        if (isset($ratingData['stores'])) {
            $stores = $this->getStoresByCodes($ratingData['stores']);
        }
        $rating->setStores($stores);
        return $rating;
    }

    /**
     * Sets the options on the rating
     */
    protected function setOptions(Rating $rating): void
    {
        $ratingOptions = $rating->getOptions();
        if (count($ratingOptions) === self::MAX_NUM_RATINGS) {
            return;
        }
        $alreadyCreated = [];

        foreach ($ratingOptions as $ratingOption) {
            $alreadyCreated[] = $ratingOption->getCode();
        }
        for ($count = 1; $count <= self::MAX_NUM_RATINGS; $count++) {
            if (in_array($count, $alreadyCreated)) {
                continue;
            }
            /**
             * @var Option $option
             */
            $option = $this->optionFactory->create();
            $option->setRatingId($rating->getId());
            $option->setCode($count);
            $option->setValue($count);
            $option->setPosition($count);
            $option->save();
        }
    }

    public function getStoresByCodes(mixed $storeCodes): array
    {
        $storesResponse = [];

        if (!is_array($storeCodes)) {
            $storeCodes[] = $storeCodes;
        }

        foreach ($storeCodes as $storeCode) {
            $storeModel = $this->storeRepository->get($storeCode);
            $storesResponse[] = $storeModel->getId();
        }

        return $storesResponse;
    }

    /**
     * Get the review entity ID
     */
    private function getReviewEntityId(): mixed
    {
        if ($this->entityId === null) {
            /**
             * @var Entity $entity
             */
            $entity = $this->entityFactory->create();
            $this->entityId = $entity->getIdByCode('product');
        }
        return $this->entityId;
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
