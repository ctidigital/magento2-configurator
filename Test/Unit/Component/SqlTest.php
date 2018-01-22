<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Processor\SqlSplitProcessor;
use CtiDigital\Configurator\Component\Sql;

class SqlTest extends ComponentAbstractTestCase
{
    protected function componentSetUp()
    {
        /** @var SqlSplitProcessor|\PHPUnit_Framework_MockObject_MockObject $mockSqlSplitProc */
        $mockSqlSplitProc = $this->getMockBuilder(SqlSplitProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->component = new Sql(
            $this->logInterface,
            $this->objectManager,
            $mockSqlSplitProc
        );

        $this->className = Sql::class;
    }
}
