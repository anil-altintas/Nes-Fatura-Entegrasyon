<?php

namespace NES\Config;

use Dotenv\Dotenv;

class Config
{
    private static $instance = null;
    private $config = [];

    private function __construct()
    {
        $this->loadEnvironment();
        $this->loadConfig();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnvironment(): void
    {
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }
    }

    private function loadConfig(): void
    {
        $this->config = [
            'api' => [
                'base_url' => $_ENV['NES_API_BASE_URL'] ?? 'https://developertest.nes.com.tr/api',
                'username' => $_ENV['NES_API_USERNAME'] ?? '',
                'password' => $_ENV['NES_API_PASSWORD'] ?? '',
                'client_id' => $_ENV['NES_API_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['NES_API_CLIENT_SECRET'] ?? '',
            ],
            'logging' => [
                'level' => $_ENV['LOG_LEVEL'] ?? 'INFO',
                'file' => $_ENV['LOG_FILE'] ?? 'logs/nes_fatura.log',
            ],
            'test_mode' => filter_var($_ENV['TEST_MODE'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        ];
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public function getAll(): array
    {
        return $this->config;
    }
}
