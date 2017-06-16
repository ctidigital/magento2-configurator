<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\ReviewRating;

class ReviewRatingTest extends ComponentAbstractTestCase
{
    protected function componentSetUp()
    {
        $this->component = $this->testObjectManager->getObject(ReviewRating::class);
        $this->className = ReviewRating::class;
    }

    /**
     * Test get the review ratings from the data
     */
    public function testGetReviewRatings()
    {
        $data = [
            'review_rating'    => [
                'Quality'   => [],
                'Value'     => [],
                'Price'     => []
            ]
        ];

        $expectedData = [
            'Quality'   => [],
            'Value'     => [],
            'Price'     => []
        ];

        /**
         * @var ReviewRating $reviewRating
         */
        $reviewRating = $this->testObjectManager->getObject(ReviewRating::class);
        $this->assertEquals($expectedData, $reviewRating->getReviewRatings($data));
    }

    /**
     * Test get the review rating model using the code
     */
    public function testGetReviewRating()
    {
        $mockRating = $this->getMockBuilder(\Magento\Review\Model\Rating::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRating->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $mockRating->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $mockRatingFactory = $this->getMockBuilder('\Magento\Review\Model\RatingFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $mockRatingFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockRating);
        /**
         * @var ReviewRating $reviewRating
         */
        $reviewRating = $this->testObjectManager->getObject(
            ReviewRating::class,
            [
                'ratingFactory' => $mockRatingFactory
            ]
        );
        $rating = $reviewRating->getReviewRating('value');
        $this->assertEquals(1, $rating->getId());
    }

    /**
     * Test get a review rating that doesn't exist
     */
    public function testGetNewRating()
    {
        $mockRating = $this->getMockBuilder(\Magento\Review\Model\Rating::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockRating->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $mockRating->expects($this->once())
            ->method('getId')
            ->willReturn(null);

        $mockRatingFactory = $this->getMockBuilder('\Magento\Review\Model\RatingFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRatingFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockRating);
        /**
         * @var ReviewRating $reviewRating
         */
        $reviewRating = $this->testObjectManager->getObject(
            ReviewRating::class,
            [
                'ratingFactory' => $mockRatingFactory
            ]
        );
        $rating = $reviewRating->getReviewRating('price');
        $this->assertNull($rating->getId());
    }

    /**
     * Test updating a review rating
     */
    public function testUpdateOrCreateRating()
    {
        $mockStoreRepository = $this->getMockBuilder(\Magento\Store\Model\StoreRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStoreA = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStoreA->expects($this->once())
            ->method('getId')
            ->willReturn(4);

        $mockStoreB = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStoreB->expects($this->once())
            ->method('getId')
            ->willReturn(5);

        $mockStoreRepository->expects($this->at(0))
            ->method('get')
            ->with('store_en')
            ->willReturn($mockStoreA);

        $mockStoreRepository->expects($this->at(1))
            ->method('get')
            ->with('store_de')
            ->willReturn($mockStoreB);

        /**
         * @var \Magento\Review\Model\Rating $rating
         */
        $rating = $this->testObjectManager->getObject(\Magento\Review\Model\Rating::class);

        $ratingCode = 'Test_Review';

        $textData = [
            'is_active'    => 1,
            'position'  => 1,
            'stores'    => [
                'store_en',
                'store_de'
            ]
        ];

        $expectedStores = [4, 5];

        /**
         * @var ReviewRating $reviewRating
         */
        $reviewRating = $this->testObjectManager->getObject(
            ReviewRating::class,
            [
                'storeRepository' => $mockStoreRepository
            ]
        );
        $updatedRating = $reviewRating->updateOrCreateRating($rating, $ratingCode, $textData);
        $this->assertEquals($ratingCode, $updatedRating->getRatingCode());
        $this->assertEquals($textData['is_active'], $updatedRating->getData('is_active'));
        $this->assertEquals($textData['position'], $updatedRating->getData('position'));
        $this->assertEquals($expectedStores, $updatedRating->getStores());
    }
}
