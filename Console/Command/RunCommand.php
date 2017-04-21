<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Model\Configurator\ConfigInterface;
use CtiDigital\Configurator\Model\ConfiguratorAdapterInterface;
use CtiDigital\Configurator\Model\Exception\ConfiguratorAdapterException;
use CtiDigital\Configurator\Model\Processor;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    protected $processor;

    public function __construct(
        ConfiguratorAdapterInterface $configuratorAdapter,
        ConfigInterface $config,
        ObjectManagerInterface $objectManager,
        Processor $processor
    ) {
        parent::__construct();
        $this->configuratorAdapter = $configuratorAdapter;
        $this->configInterface = $config;
        $this->objectManager = $objectManager;
        $this->processor = $processor;
    }

    protected function configure()
    {
        $environmentOption = new InputOption(
            'env',
            'e',
            InputOption::VALUE_REQUIRED,
            'Specify environment configuration'
        );

        $componentOption = new InputOption(
            'component',
            'c',
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Test',
            array()
        );

        $this
            ->setName('configurator:run')
            ->setDescription('Run configurator components')
            ->setDefinition(
                new InputDefinition(array(
                    $environmentOption,
                    $componentOption
                ))
            );
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

            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln('<comment>Starting Configurator</comment>');
            }

            $environment = $input->getOption('env');
            $components = $input->getOption('component');

            $logLevel = OutputInterface::VERBOSITY_NORMAL;
            $verbose = $input->getOption('verbose');

            if ($environment == null) {
                throw new ConfiguratorAdapterException('Please specify an environment using --env="<environment>"');
            }

            if ($verbose) {
                $logLevel = OutputInterface::VERBOSITY_VERBOSE;
            }

            $this->processor->setEnvironment($environment);

            foreach ($components as $component) {
                $this->processor->addComponent($component);
            }

            $this->processor->getLogger()->setLogLevel($logLevel);
            $this->processor->run();

            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln('<comment>Finished Configurator</comment>');
            }

        } catch (ConfiguratorAdapterException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
