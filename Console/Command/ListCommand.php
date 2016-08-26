<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Model\ComponentList;
use CtiDigital\Configurator\Model\Configurator\ConfigInterface;
use CtiDigital\Configurator\Model\ConfiguratorAdapterInterface;
use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\Exception\ConfiguratorAdapterException;
use Magento\Framework\App\ObjectManagerFactory;
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
     * @var CtiDigital\Configurator\Console\Command\ListCommand
     */
    private $objectManagerFactory;

    public function __construct(
        ConfiguratorAdapterInterface $configuratorAdapter,
        ConfigInterface $config,
        ObjectManagerFactory $objectManagerFactory
    ) {
        parent::__construct();
        $this->objectManagerFactory = $objectManagerFactory;
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
     * @SuppressWarnings(PHPMD)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {

            $objectManager = $this->objectManagerFactory->create(array());

            $count = 1;
            foreach ($this->configInterface->getAllComponents() as $component) {

                /* @var \CtiDigital\Configurator\Model\Component\ComponentAbstract $componentClass */
                $componentClass = $objectManager->create($component['class']);
                $comment =
                    str_pad($count.')', 3)
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
