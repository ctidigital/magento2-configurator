<?php
namespace CtiDigital\Configurator\Model;

interface LoggingInterface
{

    const LEVEL_INFO = 'info';
    const LEVEL_COMMENT = 'comment';
    const LEVEL_QUESTION = 'question';
    const LEVEL_ERROR = 'error';

    public function log($message, $level);

    public function logError($message);

    public function logQuestion($message);

    public function logComment($message);

    public function logInfo($message);
}
