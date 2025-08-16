<?php

namespace NES\Logger;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class Logger
{
    private static $instance = null;
    private $logger;

    private function __construct()
    {
        $this->initializeLogger();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeLogger(): void
    {
        $config = \NES\Config\Config::getInstance();
        
        $this->logger = new MonologLogger('NES_Fatura');
        
        // Log dizinini oluştur
        $logDir = dirname($config->get('logging.file'));
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Dosya handler'ı ekle
        $fileHandler = new RotatingFileHandler(
            $config->get('logging.file'),
            30, // 30 günlük rotasyon
            $this->getLogLevel($config->get('logging.level'))
        );

        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s"
        );
        
        $fileHandler->setFormatter($formatter);
        $this->logger->pushHandler($fileHandler);
    }

    private function getLogLevel(string $level): int
    {
        $levels = [
            'DEBUG' => MonologLogger::DEBUG,
            'INFO' => MonologLogger::INFO,
            'NOTICE' => MonologLogger::NOTICE,
            'WARNING' => MonologLogger::WARNING,
            'ERROR' => MonologLogger::ERROR,
            'CRITICAL' => MonologLogger::CRITICAL,
            'ALERT' => MonologLogger::ALERT,
            'EMERGENCY' => MonologLogger::EMERGENCY,
        ];

        return $levels[strtoupper($level)] ?? MonologLogger::INFO;
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }
}
