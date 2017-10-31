<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\ReviewRating;

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
            ->setMethods(['load', 'getId'])
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
            ->setMethods(['load', 'getId'])
            ->getMock();

        $mockRating->expects($this->once())
            ->method('load')
            ->willReturnSelf();

        $mockRating->expects($this->once())
            ->method('getId')
            ->willReturn(null);

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
        $rating = $reviewRating->getReviewRating('price');
        $this->assertNull($rating->getId());
    }
}
