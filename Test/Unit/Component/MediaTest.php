<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Model\Component\Media;

class MediaTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $directoryList = $this->getMock(\Magento\Framework\App\Filesystem\DirectoryList::class, [], [], '', false);
        $this->component = new Media($this->logInterface, $this->objectManager, $directoryList);
        $this->className = Media::class;
    }
}
