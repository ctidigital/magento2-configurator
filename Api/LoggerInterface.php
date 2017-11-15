<?php
namespace CtiDigital\Configurator\Api;

interface LoggerInterface
{

    const LEVEL_INFO = 'info';
    const LEVEL_COMMENT = 'comment';
    const LEVEL_QUESTION = 'question';
    const LEVEL_ERROR = 'error';

    public function log($message, $level, $nest = 0);

    public function logError($message, $nest = 0);

    public function logQuestion($message, $nest = 0);

    public function logComment($message, $nest = 0);

    public function logInfo($message, $nest = 0);
}
