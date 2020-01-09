<?php

namespace CtiDigital\Configurator\Test\Unit\Component;

use CtiDigital\Configurator\Component\Media;

class MediaTest extends ComponentAbstractTestCase
{

    protected function componentSetUp()
    {
        $directoryList = $this->getMockBuilder(\Magento\Framework\App\Filesystem\DirectoryList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->component = new Media($this->logInterface, $this->objectManager, $this->json, $directoryList);
        $this->className = Media::class;
    }
}
