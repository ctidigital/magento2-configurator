<?php
/**
 * @package  CtiDigital\Configurator
 * @author Bartosz Herba <bartoszherba@gmail.com>
 * @copyright 2017 CtiDigital
 */

namespace CtiDigital\Configurator\Component\Factory;

use CtiDigital\Configurator\Component\ComponentAbstract;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class ComponentFactory
 */
class ComponentFactory implements ComponentFactoryInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * ComponentFactory constructor.
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param string $componentClass
     * @param array $args
     *
     * @return ComponentAbstract
     */
    public function create($componentClass, array $args = [])
    {
        return $this->objectManager->create($componentClass, $args);
    }
}
