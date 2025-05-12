<?php

namespace CtiDigital\Configurator\Model\Component;

use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\Driver\File;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\UrlRewrite\Model\UrlRewrite;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use PHPUnit\Framework\TestCase;

class RewritesTest extends TestCase
{
    private $rewritesCsvPath;

    /**
     * @var Rewrites
     *
     * Class under test
     */
    private $rewritesComponent;

    private $rewritesData;

    private $expectedRewrites;

    /**
     * @var UrlRewrite
     */
    private $urlRewriteModel;

    public function setUp(): void
    {
        $this->expectedRewrites = $this->getExpectedRewrites();

        $this->rewritesCsvPath = sprintf("%s/../../Samples/Components/Rewrites/rewrites.csv", __DIR__);
        $this->rewritesComponent = Bootstrap::getObjectManager()
            ->get('CtiDigital\Configurator\Model\Component\Rewrites');

        /**
         * @var UrlRewriteFactory
         */
        $urlRewriteFactory = Bootstrap::getObjectManager()
            ->get('Magento\UrlRewrite\Model\UrlRewriteFactory');

        $this->urlRewriteModel = $urlRewriteFactory->create();

        $file = new File();
        $this->rewritesData = new Csv($file);
        $this->rewritesData = $this->rewritesData->getData($this->rewritesCsvPath);
    }

    public function testShouldCreateNewRewritesFromCsv()
    {
        // given a CSV file containing rewrites (created in setUp)

        // when we run the Rewrites component
        $this->rewritesComponent->processData($this->rewritesData);

        // then it should create rewrites in the database
        $this->assertThatExpectedRewritesExist($this->expectedRewrites);
    }

    private function assertThatExpectedRewritesExist(array $rewrites)
    {
        foreach ($rewrites as $rewrite) {
            $this->assertThatExpectedRewriteExists($rewrite);
        }
    }

    private function assertThatExpectedRewriteExists(Rewrite $expectedRewrite)
    {
        $actualRewrite = $this->urlRewriteModel->getCollection()
            ->addFieldToFilter("request_path", $expectedRewrite->getRequestPath())
            ->getFirstItem()
            ->getData();

        $this->assertEquals($actualRewrite['request_path'], $expectedRewrite->getRequestPath());
        $this->assertEquals($actualRewrite['target_path'], $expectedRewrite->getTargetPath());
        $this->assertEquals($actualRewrite['redirect_type'], $expectedRewrite->getRedirectType());
        $this->assertEquals($actualRewrite['store_id'], $expectedRewrite->getStoreId());
        $this->assertEquals($actualRewrite['description'], $expectedRewrite->getDescription());
    }

    public function getExpectedRewrites()
    {
        return [
            new Rewrite(
                "aaa",
                "aab",
                "302",
                "1",
                "Redirect One"
            ),
            new Rewrite(
                "bbb",
                "bbc",
                "302",
                "1",
                "Redirect Two"
            ),
            new Rewrite(
                "aac",
                "aad",
                "302",
                "1",
                "Redirect Three"
            )
        ];
    }
}
