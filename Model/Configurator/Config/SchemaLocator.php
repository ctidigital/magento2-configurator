<?php

namespace CtiDigital\Configurator\Model\Configurator\Config;

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir;

class SchemaLocator implements SchemaLocatorInterface
{

    protected $schema;

    public function __construct(\Magento\Framework\Module\Dir\Reader $reader)
    {
        $this->schema =
            $reader->getModuleDir(Dir::MODULE_ETC_DIR, 'CtiDigital_Configurator')
            . '/'
            . 'configurator.xsd';
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema()
    {
        $this->schema;
    }

    /**
     * Only one schema will every be required once merged.
     *
     * @return null
     */
    public function getPerFileSchema()
    {
        return null;
    }
}
