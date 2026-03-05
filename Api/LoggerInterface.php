<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Api;

interface LoggerInterface
{
    public const LEVEL_INFO = 'info';
    public const LEVEL_COMMENT = 'comment';
    public const LEVEL_QUESTION = 'question';
    public const LEVEL_ERROR = 'error';

    /**
     * Log a message at the given level with optional nesting depth.
     */
    public function log(string $message, string $level, int $nest = 0): void;

    /**
     * Log an error message.
     */
    public function logError(string $message, int $nest = 0): void;

    /**
     * Log a question message.
     */
    public function logQuestion(string $message, int $nest = 0): void;

    /**
     * Log a comment message.
     */
    public function logComment(string $message, int $nest = 0): void;

    /**
     * Log an info message.
     */
    public function logInfo(string $message, int $nest = 0): void;
}
