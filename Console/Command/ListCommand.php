<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Model\ComponentList;
use CtiDigital\Configurator\Model\Configurator\ConfigInterface;
use CtiDigital\Configurator\Model\ConfiguratorAdapterInterface;
use CtiDigital\Configurator\Model\Exception\ComponentException;
use CtiDigital\Configurator\Model\Exception\ConfiguratorAdapterException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{
    /**
     * @var ConfiguratorAdapterInterface
     */
    private $configuratorAdapter;

    private $configInterface;

    public function __construct(ConfiguratorAdapterInterface $configuratorAdapter, ConfigInterface $config)
    {
        parent::__construct();
        $this->configuratorAdapter = $configuratorAdapter;
        $this->configInterface = $config;
    }

    protected function configure()
    {
        $this->setName('configurator:list');
        $this->setDescription('List configurator components');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {

            foreach ($this->configInterface->getAllComponents() as $component) {
                $output->writeln('<comment>' . $count . ')' . $component . '</comment>');
                $count++;
            }
        } catch (ConfiguratorAdapterException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
