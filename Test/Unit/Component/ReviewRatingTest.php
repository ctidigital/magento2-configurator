<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\ReviewRating;
use Magento\Review\Model\RatingFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Review\Model\Rating\OptionFactory;
use Magento\Review\Model\Rating\EntityFactory;
use CtiDigital\Configurator\Api\LoggerInterface;

class ReviewRatingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ReviewRating
     */
    private $reviewRating;

    /**
     * @var RatingFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ratingFactory;

    /**
     * @var StoreRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storeRepository;

    /**
     * @var OptionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $optionFactory;

    /**
     * @var EntityFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $log;

    protected function setUp(): void
    {
        $this->ratingFactory = $this->getMockBuilder(RatingFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->storeRepository = $this->getMockBuilder(StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->optionFactory = $this->getMockBuilder(OptionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityFactory = $this->getMockBuilder(EntityFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->log = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reviewRating = new ReviewRating(
            $this->ratingFactory,
            $this->storeRepository,
            $this->optionFactory,
            $this->entityFactory,
            $this->log
        );
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

        $this->assertEquals($expectedData, $this->reviewRating->getReviewRatings($data));
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

        $this->ratingFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockRating);
        $rating = $this->reviewRating->getReviewRating('value');
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

        $this->ratingFactory->expects($this->once())
            ->method('create')
            ->willReturn($mockRating);

        $rating = $this->reviewRating->getReviewRating('price');
        $this->assertNull($rating->getId());
    }
}
