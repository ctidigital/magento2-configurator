<?php

namespace CtiDigital\Configurator\Model;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Logging implements LoggerInterface
{

    protected $output;
    protected $level;

    public function __construct(ConsoleOutput $output, $level = OutputInterface::VERBOSITY_NORMAL)
    {
        $this->output = $output;
        $this->level = $level;
    }

    public function setLogLevel($level = OutputInterface::VERBOSITY_NORMAL)
    {
        $this->level = $level;
        return $this;
    }

    public function getLogLevel()
    {
        return $this->level;
    }

    public function log($message, $level, $nest = 0)
    {
        $prepend = '';
        for ($i = 0; $i < $nest; $i++) {
            $prepend .= "| ";
        }
        $this->output->writeln($prepend . '<' . $level . '>' . $message . '</' . $level . '>');
    }

    public function logError($message, $nest = 0)
    {
        $this->log($message, $this::LEVEL_ERROR, $nest);
    }

    public function logQuestion($message, $nest = 0)
    {
        $this->log($message, $this::LEVEL_QUESTION, $nest);
    }

    public function logComment($message, $nest = 0)
    {
        if ($this->level > OutputInterface::VERBOSITY_NORMAL) {
            $this->log($message, $this::LEVEL_COMMENT, $nest);
        }
    }

    public function logInfo($message, $nest = 0)
    {
        $this->log($message, $this::LEVEL_INFO, $nest);
    }
}
