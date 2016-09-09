<?php

namespace CtiDigital\Configurator\Model;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Logging implements LoggingInterface
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
        $this->output->writeln('<' . $level . '>' . $message . '<' . $level . '>');
    }

    public function logError($message)
    {
        $this->log($message, $this::LEVEL_ERROR);
    }

    public function logQuestion($message)
    {
        $this->log($message, $this::LEVEL_QUESTION);
    }

    public function logComment($message)
    {
        if ($this->level > OutputInterface::VERBOSITY_NORMAL) {
            $this->log($message, $this::LEVEL_COMMENT);
        }
    }

    public function logInfo($message)
    {
        $this->log($message, $this::LEVEL_INFO);
    }
}
