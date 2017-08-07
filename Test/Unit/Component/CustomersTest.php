<?php
namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Customers;
use CtiDigital\Configurator\Model\Exception\ComponentException;

class CustomersTest extends ComponentAbstractTestCase
{
    protected function componentSetUp()
    {
        $this->component = $this->testObjectManager->getObject(Customers::class);
        $this->className = Customers::class;
    }

    public function testDataMissingRows()
    {
        $testData = [];
        $this->setExpectedException(ComponentException::class);
        $this->component->getColumnHeaders($testData);
    }

    public function testRequiredColumns()
    {
        $testData = [['email', '_website', '_store', 'firstname', 'lastname']];
        $this->component->getColumnHeaders($testData);
    }

    public function testColumnsNotFound()
    {
        $testData = [['_website', '_store', 'firstname', 'notallowed']];
        $this->setExpectedException(ComponentException::class, 'The column "email" is required.');
        $this->component->getColumnHeaders($testData);
    }

    public function testGetColumns()
    {
        $expected = ['email', '_website', '_store', 'firstname', 'lastname'];
        $testData = [
            ['email', '_website', '_store', 'firstname', 'lastname'],
            ['example@example.com', 'base', 'Default', 'Test', 'Test']
        ];
        $this->component->getColumnHeaders($testData);
        $this->assertEquals($expected, $this->component->getHeaders());

    }
}
