<?php

namespace CtiDigital\Configurator\Model;

use Symfony\Component\Console\Output\OutputInterface;

class Logging implements LoggingInterface
{

    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function log($message,$level)
    {
        $this->output->writeln('<'.$level.'>'.$message.'<'.$level.'>');
    }

    public function logError($message)
    {
        $this->log($message,$this::LEVEL_ERROR);
    }

    public function logQuestion($message)
    {
        $this->log($message,$this::LEVEL_QUESTION);
    }

    public function logComment($message)
    {
        $this->log($message,$this::LEVEL_COMMENT);
    }

    public function logInfo($message)
    {
        $this->log($message,$this::LEVEL_INFO);
    }

}
