<?php
namespace CtiDigital\Configurator\Model\Component;

use CtiDigital\Configurator\Model\LoggingInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Review\Model\Rating;
use Magento\Review\Model\RatingFactory;
use Magento\Store\Model\StoreRepository;

class ReviewRating extends YamlComponentAbstract
{
    protected $alias = 'review_rating';

    protected $name = 'Review Rating';

    /**
     * @var RatingFactory
     */
    protected $ratingFactory;

    /**
     * @var StoreRepository
     */
    protected $storeRepository;

    public function __construct(
        LoggingInterface $log,
        ObjectManagerInterface $objectManager,
        RatingFactory $ratingFactory,
        StoreRepository $storeRepository
    ) {
        $this->ratingFactory = $ratingFactory;
        $this->storeRepository = $storeRepository;
        parent::__construct($log, $objectManager);
    }

    public function processData($data = null)
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
                $this->log->logInfo(__('Updated review rating "%1"', $code));
            } catch (\Exception $e) {
                $this->log->logError(__('Failed updating review rating "%1". Error message: %2', $code, $e->getMessage()));
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

    public function updateOrCreateRating(Rating $rating, $ratingCode, $ratingData)
    {
        $rating->setRatingCode($ratingCode);
        $rating->setEntityId(1);
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
}
