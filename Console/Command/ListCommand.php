<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Model\ConfiguratorAdapterInterface;
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

    public function __construct(ConfiguratorAdapterInterface $configuratorAdapter)
    {
        parent::__construct();
        $this->configuratorAdapter = $configuratorAdapter;
    }

    protected function configure()
    {
        $this->setName('configurator:list');
        $this->setDescription('List configurator components');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            
            $output->writeln('<comment>To do</comment>');
        } catch (ConfiguratorAdapterException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}