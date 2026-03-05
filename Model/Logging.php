<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Model;

use CtiDigital\Configurator\Api\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Logging implements LoggerInterface
{
    protected ConsoleOutput $output;
    protected int $level;

    public function __construct(ConsoleOutput $output, int $level = OutputInterface::VERBOSITY_NORMAL)
    {
        $this->output = $output;
        $this->level = $level;
    }

    /**
     * Set the log verbosity level.
     *
     * @return $this
     */
    public function setLogLevel(int $level = OutputInterface::VERBOSITY_NORMAL): static
    {
        $this->level = $level;
        return $this;
    }

    /**
     * Get the current log verbosity level.
     */
    public function getLogLevel(): int
    {
        return $this->level;
    }

    public function log(string $message, string $level, int $nest = 0): void
    {
        $prepend = '';
        for ($i = 0; $i < $nest; $i++) {
            $prepend .= "| ";
        }
        $this->output->writeln($prepend . '<' . $level . '>' . $message . '</' . $level . '>');
    }

    public function logError(string $message, int $nest = 0): void
    {
        $this->log($message, $this::LEVEL_ERROR, $nest);
    }

    public function logQuestion(string $message, int $nest = 0): void
    {
        $this->log($message, $this::LEVEL_QUESTION, $nest);
    }

    public function logComment(string $message, int $nest = 0): void
    {
        if ($this->level > OutputInterface::VERBOSITY_NORMAL) {
            $this->log($message, $this::LEVEL_COMMENT, $nest);
        }
    }

    public function logInfo(string $message, int $nest = 0): void
    {
        if ($this->level > OutputInterface::VERBOSITY_QUIET) {
            $this->log($message, $this::LEVEL_INFO, $nest);
        }
    }
}
