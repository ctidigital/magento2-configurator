<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Model\Configurator\ConfigInterface;
use CtiDigital\Configurator\Model\ConfiguratorAdapterInterface;
use CtiDigital\Configurator\Model\Exception\ConfiguratorAdapterException;
use CtiDigital\Configurator\Model\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    /**
     * @var ConfiguratorAdapterInterface
     */
    private $configuratorAdapter;

    /**
     * @var ConfigInterface|CtiDigital\Configurator\Console\Command\RunCommand
     */
    private $configInterface;

    public function __construct(
        ConfiguratorAdapterInterface $configuratorAdapter,
        ConfigInterface $config
    ) {
        parent::__construct();
        $this->configuratorAdapter = $configuratorAdapter;
        $this->configInterface = $config;
    }

    protected function configure()
    {
        $this->setName('configurator:run');
        $this->setDescription('Run configurator components');
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

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln('<info>Starting Configurator</info>');
            }

            $processor = new Processor($output);
            $processor->run();

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln('<info>Finished Configurator</info>');
            }

        } catch (ConfiguratorAdapterException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
