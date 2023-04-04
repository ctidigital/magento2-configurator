<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Exception\ConfiguratorAdapterException;
use CtiDigital\Configurator\Model\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    /**
     * @param Processor $processor
     */
    public function __construct(
        private readonly Processor $processor
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
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
            'The component to be run',
            []
        );

        $ignoreMissingFiles = new InputOption(
            'ignore-missing-files',
            'i',
            InputOption::VALUE_OPTIONAL,
            'Configurator continues if a source file is missing',
            false
        );

        $this
            ->setName('configurator:run')
            ->setDescription('Run configurator components')
            ->setDefinition(
                new InputDefinition([
                    $environmentOption,
                    $componentOption,
                    $ignoreMissingFiles
                ])
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @SuppressWarnings(PHPMD)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln('<comment>Starting Configurator</comment>');
            }

            $environment = $input->getOption('env');
            $components = $input->getOption('component');

            if ($input->getOption('ignore-missing-files') !== false) {
                $this->processor->setIgnoreMissingFiles(true);
            }

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

            return self::SUCCESS;

        } catch (ConfiguratorAdapterException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return self::INVALID;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return self::FAILURE;
        }
    }
}
