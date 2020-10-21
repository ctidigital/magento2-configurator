<?php
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

    protected $alias = 'review_rating';

    protected $name = 'Review Rating';

    protected $description = 'Component to create review ratings';

    protected $entityId;

    /**
     * @var RatingFactory
     */
    protected $ratingFactory;

    /**
     * @var StoreRepository
     */
    protected $storeRepository;

    /**
     * @var OptionFactory
     */
    protected $optionFactory;

    /**
     * @var EntityFactory
     */
    protected $entityFactory;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * ReviewRating constructor.
     * @param RatingFactory $ratingFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param OptionFactory $optionFactory
     * @param EntityFactory $entityFactory
     * @param LoggerInterface $log
     */
    public function __construct(
        RatingFactory $ratingFactory,
        StoreRepositoryInterface $storeRepository,
        OptionFactory $optionFactory,
        EntityFactory $entityFactory,
        LoggerInterface $log
    ) {
        $this->ratingFactory = $ratingFactory;
        $this->storeRepository = $storeRepository;
        $this->optionFactory = $optionFactory;
        $this->entityFactory = $entityFactory;
        $this->log = $log;
    }

    public function execute($data = null)
    {
        $reviewRatings = $this->getReviewRatings($data);

        foreach ($reviewRatings as $code => $reviewRating) {
            try {
                /**
                 * @var Rating $ratingModel
                 */
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
     *
     * @param $data
     *
     * @return []
     */
    public function getReviewRatings($data)
    {
        if (isset($data['review_rating'])) {
            return $data['review_rating'];
        }
        return [];
    }

    /**
     * @param $reviewRatingCode
     *
     * @return Rating
     */
    public function getReviewRating($reviewRatingCode)
    {
        /**
         * @var Rating $rating
         */
        $rating = $this->ratingFactory->create();
        $rating->load($reviewRatingCode, 'rating_code');
        return $rating;
    }

    /**
     * @param Rating $rating
     * @param $ratingCode
     * @param $ratingData
     *
     * @return Rating
     */
    public function updateOrCreateRating(Rating $rating, $ratingCode, $ratingData)
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
     *
     * @param Rating $rating
     */
    protected function setOptions(Rating $rating)
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

    /**
     * @param $storeCodes
     *
     * @return array
     */
    public function getStoresByCodes($storeCodes)
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
     *
     * @return int
     */
    private function getReviewEntityId()
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
