<?php

namespace CtiDigital\Configurator\Console\Command;

use CtiDigital\Configurator\Api\ComponentListInterface;
use CtiDigital\Configurator\Exception\ConfiguratorAdapterException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{
    /**
     * @var ComponentListInterface
     */
    private $componentList;

    /**
     * ListCommand constructor.
     * @param ComponentListInterface $componentList
     */
    public function __construct(
        ComponentListInterface $componentList
    ) {
        parent::__construct();
        $this->componentList = $componentList;
    }

    protected function configure()
    {
        $this->setName('configurator:list');
        $this->setDescription('List configurator components');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $count = 1;
            foreach ($this->componentList->getAllComponents() as $component) {
                $comment =
                    str_pad($count.') ', 4)
                    . str_pad($component->getAlias(), 20)
                    . ' - ' . $component->getDescription();
                $output->writeln('<comment>' . $comment . '</comment>');
                $count++;
            }
        } catch (ConfiguratorAdapterException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 1;
        }
        return 0;
    }
}
