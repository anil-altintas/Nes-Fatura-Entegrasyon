<?php

namespace NES\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use NES\Config\Config;
use NES\Exception\NESException;
use NES\Logger\Logger;

class NESApiClient
{
    private Client $httpClient;
    private Config $config;
    private Logger $logger;
    private ?string $accessToken = null;
    private ?int $tokenExpiry = null;

    public function __construct()
    {
        $this->config = Config::getInstance();
        $this->logger = Logger::getInstance();
        
        $this->httpClient = new Client([
            'base_uri' => $this->config->get('api.base_url'),
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'NES-Fatura-PHP-Client/1.0',
            ],
        ]);
    }

    /**
     * API'ye kimlik doğrulama yapar ve access token alır
     */
    public function authenticate(): string
    {
        // Token hala geçerli mi kontrol et
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }

        try {
            $this->logger->info('NES API kimlik doğrulama başlatılıyor');
            
            $response = $this->httpClient->post('/auth/token', [
                'json' => [
                    'username' => $this->config->get('api.username'),
                    'password' => $this->config->get('api.password'),
                    'client_id' => $this->config->get('api.client_id'),
                    'client_secret' => $this->config->get('api.client_secret'),
                    'grant_type' => 'password',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($data['access_token'])) {
                throw NESException::authenticationFailed('Access token alınamadı');
            }

            $this->accessToken = $data['access_token'];
            $this->tokenExpiry = time() + ($data['expires_in'] ?? 3600) - 300; // 5 dakika önce yenile
            
            $this->logger->info('NES API kimlik doğrulama başarılı');
            
            return $this->accessToken;
            
        } catch (GuzzleException $e) {
            $this->logger->error('NES API kimlik doğrulama hatası: ' . $e->getMessage());
            throw NESException::authenticationFailed('Kimlik doğrulama hatası: ' . $e->getMessage());
        }
    }

    /**
     * API'ye POST isteği gönderir
     */
    public function post(string $endpoint, array $data = []): array
    {
        $token = $this->authenticate();
        
        try {
            $this->logger->debug("POST isteği gönderiliyor: {$endpoint}", $data);
            
            $response = $this->httpClient->post($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'json' => $data,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->debug("POST yanıtı alındı: {$endpoint}", $responseData);
            
            return $responseData;
            
        } catch (GuzzleException $e) {
            $this->logger->error("POST isteği hatası: {$endpoint} - " . $e->getMessage());
            throw NESException::serverError("API isteği hatası: " . $e->getMessage());
        }
    }

    /**
     * API'ye GET isteği gönderir
     */
    public function get(string $endpoint, array $query = []): array
    {
        $token = $this->authenticate();
        
        try {
            $this->logger->debug("GET isteği gönderiliyor: {$endpoint}", $query);
            
            $response = $this->httpClient->get($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'query' => $query,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->debug("GET yanıtı alındı: {$endpoint}", $responseData);
            
            return $responseData;
            
        } catch (GuzzleException $e) {
            $this->logger->error("GET isteği hatası: {$endpoint} - " . $e->getMessage());
            throw NESException::serverError("API isteği hatası: " . $e->getMessage());
        }
    }

    /**
     * API'ye PUT isteği gönderir
     */
    public function put(string $endpoint, array $data = []): array
    {
        $token = $this->authenticate();
        
        try {
            $this->logger->debug("PUT isteği gönderiliyor: {$endpoint}", $data);
            
            $response = $this->httpClient->put($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
                'json' => $data,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->debug("PUT yanıtı alındı: {$endpoint}", $responseData);
            
            return $responseData;
            
        } catch (GuzzleException $e) {
            $this->logger->error("PUT isteği hatası: {$endpoint} - " . $e->getMessage());
            throw NESException::serverError("API isteği hatası: " . $e->getMessage());
        }
    }

    /**
     * API'ye DELETE isteği gönderir
     */
    public function delete(string $endpoint): array
    {
        $token = $this->authenticate();
        
        try {
            $this->logger->debug("DELETE isteği gönderiliyor: {$endpoint}");
            
            $response = $this->httpClient->delete($endpoint, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                ],
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->debug("DELETE yanıtı alındı: {$endpoint}", $responseData);
            
            return $responseData;
            
        } catch (GuzzleException $e) {
            $this->logger->error("DELETE isteği hatası: {$endpoint} - " . $e->getMessage());
            throw NESException::serverError("API isteği hatası: " . $e->getMessage());
        }
    }

    /**
     * Test modunda mı kontrol eder
     */
    public function isTestMode(): bool
    {
        return $this->config->get('test_mode', false);
    }
}
