<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <bartoszherba@gmail.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Test\Unit\Processor;

use CtiDigital\Configurator\Api\LoggerInterface;
use CtiDigital\Configurator\Component\Processor\SqlSplitProcessor;
use CtiDigital\Configurator\Model\Logging;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class SqlSplitProcessorTest
 * @codingStandardsIgnoreStart
 */
class SqlSplitProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockLogger;

    /**
     * @var ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockResource;

    /**
     * @var AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockConnection;

    /**
     * @var SqlSplitProcessor
     */
    protected $processor;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->mockLogger = $this->getMockBuilder(Logging::class)
            ->disableOriginalConstructor()
            ->setMethods(['logInfo', 'logError'])
            ->getMock();
        $this->mockResource = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection'])
            ->getMock();

        $this->mockConnection = $this->getMockBuilder(Mysql::class)
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'query', 'rollBack', 'commit'])
            ->getMock();

        $this->mockResource->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->mockConnection);

        $this->processor = $this->objectManager->getObject(SqlSplitProcessor::class, [
            'log'      => $this->mockLogger,
            'resource' => $this->mockResource,
        ]);
    }

    public function sqlDataProvider()
    {
        return [
            ['sitemap', "SELECT * FROM sitemap;\nANOTHER QUERY;", 2],
            ['another', 'DELETE from store;', 1],
            ['empty', '', 0],
        ];
    }

    public function testExceptionHandling()
    {
        $name = 'name1';
        $fileContent = 'SELECT * FROM unknown';
        $exMsg = 'exception message';

        $this->mockConnection
            ->expects($this->any())
            ->method('query')
            ->willThrowException(new \Exception($exMsg));

        $this->mockLogger
            ->expects($this->at(1))
            ->method('logError')
            ->with($exMsg);

        $this->mockConnection
            ->expects($this->once())
            ->method('rollBack');

        $this->processor->process($name, $fileContent);
    }
}
