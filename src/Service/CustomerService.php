<?php

namespace NES\Service;

use NES\Client\NESApiClient;
use NES\Exception\NESException;
use NES\Logger\Logger;

class CustomerService
{
    private NESApiClient $apiClient;
    private Logger $logger;

    public function __construct()
    {
        $this->apiClient = new NESApiClient();
        $this->logger = Logger::getInstance();
    }

    /**
     * TCSI veya VKN ile müşteri sorgular
     */
    public function searchCustomer(string $identifier): array
    {
        try {
            $this->logger->info("Müşteri sorgulanıyor: {$identifier}");
            
            $response = $this->apiClient->get('/customers/search', [
                'identifier' => $identifier,
                'type' => $this->getIdentifierType($identifier)
            ]);

            if (!isset($response['success']) || !$response['success']) {
                throw NESException::fromApiResponse($response, 'Müşteri bulunamadı');
            }

            $this->logger->info("Müşteri bulundu: {$identifier}");
            return $response['data'] ?? [];

        } catch (NESException $e) {
            $this->logger->error("Müşteri sorgulama hatası: {$identifier} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Müşterinin e-fatura/e-arşiv durumunu kontrol eder
     */
    public function checkCustomerStatus(string $identifier): array
    {
        try {
            $this->logger->info("Müşteri durumu kontrol ediliyor: {$identifier}");
            
            $response = $this->apiClient->get('/customers/status', [
                'identifier' => $identifier,
                'type' => $this->getIdentifierType($identifier)
            ]);

            if (!isset($response['success']) || !$response['success']) {
                throw NESException::fromApiResponse($response, 'Müşteri durumu alınamadı');
            }

            $status = $response['data'] ?? [];
            
            $this->logger->info("Müşteri durumu alındı: {$identifier}", $status);
            
            return [
                'identifier' => $identifier,
                'type' => $this->getIdentifierType($identifier),
                'has_e_fatura' => $status['has_e_fatura'] ?? false,
                'has_e_arsiv' => $status['has_e_arsiv'] ?? false,
                'e_fatura_status' => $status['e_fatura_status'] ?? 'UNKNOWN',
                'e_arsiv_status' => $status['e_arsiv_status'] ?? 'UNKNOWN',
                'last_check' => date('Y-m-d H:i:s'),
                'details' => $status
            ];

        } catch (NESException $e) {
            $this->logger->error("Müşteri durumu kontrol hatası: {$identifier} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Müşteri bilgilerini günceller
     */
    public function updateCustomer(string $identifier, array $customerData): array
    {
        try {
            $this->logger->info("Müşteri güncelleniyor: {$identifier}");
            
            $response = $this->apiClient->put("/customers/{$identifier}", $customerData);

            if (!isset($response['success']) || !$response['success']) {
                throw NESException::fromApiResponse($response, 'Müşteri güncellenemedi');
            }

            $this->logger->info("Müşteri güncellendi: {$identifier}");
            return $response['data'] ?? [];

        } catch (NESException $e) {
            $this->logger->error("Müşteri güncelleme hatası: {$identifier} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Müşteri listesini getirir
     */
    public function getCustomers(array $filters = []): array
    {
        try {
            $this->logger->info("Müşteri listesi alınıyor");
            
            $response = $this->apiClient->get('/customers', $filters);

            if (!isset($response['success']) || !$response['success']) {
                throw NESException::fromApiResponse($response, 'Müşteri listesi alınamadı');
            }

            $this->logger->info("Müşteri listesi alındı");
            return $response['data'] ?? [];

        } catch (NESException $e) {
            $this->logger->error("Müşteri listesi alma hatası: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Tanımlayıcı tipini belirler (TCSI veya VKN)
     */
    private function getIdentifierType(string $identifier): string
    {
        // TCSI: 11 haneli sayı
        if (preg_match('/^\d{11}$/', $identifier)) {
            return 'TCSI';
        }
        
        // VKN: 10 haneli sayı
        if (preg_match('/^\d{10}$/', $identifier)) {
            return 'VKN';
        }
        
        // Geçersiz format
        throw NESException::invalidRequest("Geçersiz tanımlayıcı formatı: {$identifier}. TCSI (11 hane) veya VKN (10 hane) olmalıdır.");
    }

    /**
     * Tanımlayıcı formatını doğrular
     */
    public function validateIdentifier(string $identifier): bool
    {
        try {
            $this->getIdentifierType($identifier);
            return true;
        } catch (NESException $e) {
            return false;
        }
    }

    /**
     * Test modunda örnek müşteri verisi döner
     */
    public function getTestCustomerData(string $identifier): array
    {
        if (!$this->apiClient->isTestMode()) {
            throw NESException::invalidRequest("Test verisi sadece test modunda kullanılabilir");
        }

        $type = $this->getIdentifierType($identifier);
        
        return [
            'identifier' => $identifier,
            'type' => $type,
            'name' => $type === 'TCSI' ? 'Test Kişi' : 'Test Şirket A.Ş.',
            'has_e_fatura' => true,
            'has_e_arsiv' => true,
            'e_fatura_status' => 'ACTIVE',
            'e_arsiv_status' => 'ACTIVE',
            'email' => 'test@example.com',
            'phone' => '05551234567',
            'address' => 'Test Adres',
            'city' => 'İstanbul',
            'country' => 'TR',
            'tax_office' => $type === 'VKN' ? 'Test Vergi Dairesi' : null,
            'last_check' => date('Y-m-d H:i:s'),
            'is_test' => true
        ];
    }
}
