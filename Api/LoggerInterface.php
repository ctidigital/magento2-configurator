<?php
namespace CtiDigital\Configurator\Api;

interface LoggerInterface
{

    public const LEVEL_INFO = 'info';
    public const LEVEL_COMMENT = 'comment';
    public const LEVEL_QUESTION = 'question';
    public const LEVEL_ERROR = 'error';

    public function log($message, $level, $nest = 0);

    public function logError($message, $nest = 0);

    public function logQuestion($message, $nest = 0);

    public function logComment($message, $nest = 0);

    public function logInfo($message, $nest = 0);
}
