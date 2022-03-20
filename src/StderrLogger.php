<?php

declare(strict_types=1);

namespace SlackPhp\Framework;

use Psr\Log\{AbstractLogger, InvalidArgumentException, LogLevel};

use function json_encode;

class StderrLogger extends AbstractLogger
{
    private const LOG_LEVEL_MAP = [
        LogLevel::DEBUG     => 0,
        LogLevel::INFO      => 1,
        LogLevel::NOTICE    => 2,
        LogLevel::WARNING   => 3,
        LogLevel::ERROR     => 4,
        LogLevel::CRITICAL  => 5,
        LogLevel::ALERT     => 6,
        LogLevel::EMERGENCY => 7,
    ];

    private int $minLevel;

    /**
     * @var resource
     */
    private $stream;

    /**
     * @param mixed $stream
     */
    public function __construct(string $minLevel = LogLevel::WARNING, $stream = 'php://stderr')
    {
        if (!isset(self::LOG_LEVEL_MAP[$minLevel])) {
            throw new InvalidArgumentException("Invalid log level: {$minLevel}");
        }

        $this->minLevel = self::LOG_LEVEL_MAP[$minLevel];

        if (is_resource($stream)) {
            $this->stream = $stream;
        } elseif (is_string($stream)) {
            if (!$fopen = fopen($stream, 'a')) {
                throw new Exception('Unable to open stream: ' . $stream);
            }
            $this->stream = $fopen;
        } else {
            throw new InvalidArgumentException('A stream must either be a resource or a string');
        }
    }

    public function log($level, $message, array $context = [])
    {
        if (!isset(self::LOG_LEVEL_MAP[$level])) {
            throw new InvalidArgumentException("Invalid log level: {$level}");
        }

        // Don't report logs for log levels less than the min level.
        if (self::LOG_LEVEL_MAP[$level] < $this->minLevel) {
            return;
        }

        // Apply special formatting for "exception" fields.
        if (isset($context['exception'])) {
            $exception = $context['exception'];
            if ($exception instanceof Exception) {
                $context = $exception->getContext() + $context;
            }

            $context['exception'] = explode("\n", (string) $exception);
        }

        fwrite($this->stream, json_encode(compact('level', 'message', 'context')) . "\n");
    }
}
