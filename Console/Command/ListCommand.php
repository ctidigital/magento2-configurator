<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Model\ComponentList;
use CtiDigital\Configurator\Exception\ConfiguratorAdapterException;
use CtiDigital\Configurator\Api\ConfigInterface;
use CtiDigital\Configurator\Api\ConfiguratorAdapterInterface;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{
    /**
     * @var ConfiguratorAdapterInterface
     */
    private $configuratorAdapter;

    /**
     * @var ConfigInterface|CtiDigital\Configurator\Console\Command\ListCommand
     */
    private $configInterface;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManagerInterface;

    public function __construct(
        ConfiguratorAdapterInterface $configuratorAdapter,
        ConfigInterface $config,
        ObjectManagerInterface $objectManager
    ) {
        parent::__construct();
        $this->objectManagerInterface = $objectManager;
        $this->configuratorAdapter = $configuratorAdapter;
        $this->configInterface = $config;
    }

    protected function configure()
    {
        $this->setName('configurator:list');
        $this->setDescription('List configurator components');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $count = 1;
            foreach ($this->configInterface->getAllComponents() as $component) {

                /* @var \CtiDigital\Configurator\Component\ComponentAbstract $componentClass */
                $componentClass = $this->objectManagerInterface->create($component['class']);
                $comment =
                    str_pad($count.') ', 4)
                    . str_pad($componentClass->getComponentAlias(), 20)
                    . ' - ' . $componentClass->getDescription();
                $output->writeln('<comment>' . $comment . '</comment>');
                $count++;
            }
        } catch (ConfiguratorAdapterException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
