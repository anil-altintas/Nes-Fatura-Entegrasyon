<?php

namespace NES\Service;

use NES\Client\NESApiClient;
use NES\Exception\NESException;
use NES\Logger\Logger;

class InvoiceService
{
    private NESApiClient $apiClient;
    private Logger $logger;
    private CustomerService $customerService;

    public function __construct()
    {
        $this->apiClient = new NESApiClient();
        $this->logger = Logger::getInstance();
        $this->customerService = new CustomerService();
    }

    /**
     * E-Fatura oluşturur
     */
    public function createEInvoice(array $invoiceData): array
    {
        try {
            $this->logger->info("E-Fatura oluşturuluyor");
            
            // Müşteri durumunu kontrol et
            $customerStatus = $this->customerService->checkCustomerStatus($invoiceData['customer_identifier']);
            
            if (!$customerStatus['has_e_fatura']) {
                throw NESException::invalidRequest("Müşteri e-fatura kullanıcısı değil");
            }

            // Fatura verilerini doğrula
            $this->validateInvoiceData($invoiceData);
            
            // E-Fatura oluştur
            $response = $this->apiClient->post('/invoices/e-invoice', $invoiceData);

            if (!isset($response['success']) || !$response['success']) {
                throw NESException::fromApiResponse($response, 'E-Fatura oluşturulamadı');
            }

            $this->logger->info("E-Fatura oluşturuldu", ['invoice_id' => $response['data']['invoice_id'] ?? null]);
            
            return $response['data'] ?? [];

        } catch (NESException $e) {
            $this->logger->error("E-Fatura oluşturma hatası: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * E-Arşiv faturası oluşturur
     */
    public function createEArchiveInvoice(array $invoiceData): array
    {
        try {
            $this->logger->info("E-Arşiv faturası oluşturuluyor");
            
            // Müşteri durumunu kontrol et
            $customerStatus = $this->customerService->checkCustomerStatus($invoiceData['customer_identifier']);
            
            if (!$customerStatus['has_e_arsiv']) {
                throw NESException::invalidRequest("Müşteri e-arşiv kullanıcısı değil");
            }

            // Fatura verilerini doğrula
            $this->validateInvoiceData($invoiceData);
            
            // E-Arşiv faturası oluştur
            $response = $this->apiClient->post('/invoices/e-archive', $invoiceData);

            if (!isset($response['success']) || !$response['success']) {
                throw NESException::fromApiResponse($response, 'E-Arşiv faturası oluşturulamadı');
            }

            $this->logger->info("E-Arşiv faturası oluşturuldu", ['invoice_id' => $response['data']['invoice_id'] ?? null]);
            
            return $response['data'] ?? [];

        } catch (NESException $e) {
            $this->logger->error("E-Arşiv faturası oluşturma hatası: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fatura durumunu sorgular
     */
    public function getInvoiceStatus(string $invoiceId): array
    {
        try {
            $this->logger->info("Fatura durumu sorgulanıyor: {$invoiceId}");
            
            $response = $this->apiClient->get("/invoices/{$invoiceId}/status");

            if (!isset($response['success']) || !$response['success']) {
                throw NESException::fromApiResponse($response, 'Fatura durumu alınamadı');
            }

            $this->logger->info("Fatura durumu alındı: {$invoiceId}");
            return $response['data'] ?? [];

        } catch (NESException $e) {
            $this->logger->error("Fatura durumu sorgulama hatası: {$invoiceId} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fatura listesini getirir
     */
    public function getInvoices(array $filters = []): array
    {
        try {
            $this->logger->info("Fatura listesi alınıyor");
            
            $response = $this->apiClient->get('/invoices', $filters);

            if (!isset($response['success']) || !$response['success']) {
                throw NESException::fromApiResponse($response, 'Fatura listesi alınamadı');
            }

            $this->logger->info("Fatura listesi alındı");
            return $response['data'] ?? [];

        } catch (NESException $e) {
            $this->logger->error("Fatura listesi alma hatası: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Faturayı iptal eder
     */
    public function cancelInvoice(string $invoiceId, string $reason = ''): array
    {
        try {
            $this->logger->info("Fatura iptal ediliyor: {$invoiceId}");
            
            $response = $this->apiClient->post("/invoices/{$invoiceId}/cancel", [
                'reason' => $reason
            ]);

            if (!isset($response['success']) || !$response['success']) {
                throw NESException::fromApiResponse($response, 'Fatura iptal edilemedi');
            }

            $this->logger->info("Fatura iptal edildi: {$invoiceId}");
            return $response['data'] ?? [];

        } catch (NESException $e) {
            $this->logger->error("Fatura iptal hatası: {$invoiceId} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fatura PDF'ini indirir
     */
    public function downloadInvoicePDF(string $invoiceId): string
    {
        try {
            $this->logger->info("Fatura PDF'i indiriliyor: {$invoiceId}");
            
            $response = $this->apiClient->get("/invoices/{$invoiceId}/pdf");

            if (!isset($response['success']) || !$response['success']) {
                throw NESException::fromApiResponse($response, 'Fatura PDF\'i indirilemedi');
            }

            $this->logger->info("Fatura PDF'i indirildi: {$invoiceId}");
            return $response['data']['pdf_content'] ?? '';

        } catch (NESException $e) {
            $this->logger->error("Fatura PDF indirme hatası: {$invoiceId} - " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fatura verilerini doğrular
     */
    private function validateInvoiceData(array $invoiceData): void
    {
        $requiredFields = [
            'customer_identifier',
            'invoice_date',
            'due_date',
            'currency',
            'items'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($invoiceData[$field]) || empty($invoiceData[$field])) {
                throw NESException::invalidRequest("Gerekli alan eksik: {$field}");
            }
        }

        // Fatura kalemlerini kontrol et
        if (empty($invoiceData['items']) || !is_array($invoiceData['items'])) {
            throw NESException::invalidRequest("Fatura kalemleri gerekli");
        }

        foreach ($invoiceData['items'] as $index => $item) {
            $this->validateInvoiceItem($item, $index);
        }
    }

    /**
     * Fatura kalemini doğrular
     */
    private function validateInvoiceItem(array $item, int $index): void
    {
        $requiredItemFields = ['name', 'quantity', 'unit_price', 'tax_rate'];

        foreach ($requiredItemFields as $field) {
            if (!isset($item[$field])) {
                throw NESException::invalidRequest("Fatura kalemi {$index}: Gerekli alan eksik: {$field}");
            }
        }

        // Sayısal değerleri kontrol et
        if (!is_numeric($item['quantity']) || $item['quantity'] <= 0) {
            throw NESException::invalidRequest("Fatura kalemi {$index}: Geçersiz miktar");
        }

        if (!is_numeric($item['unit_price']) || $item['unit_price'] < 0) {
            throw NESException::invalidRequest("Fatura kalemi {$index}: Geçersiz birim fiyat");
        }

        if (!is_numeric($item['tax_rate']) || $item['tax_rate'] < 0) {
            throw NESException::invalidRequest("Fatura kalemi {$index}: Geçersiz vergi oranı");
        }
    }

    /**
     * Test modunda örnek fatura verisi döner
     */
    public function getTestInvoiceData(string $customerIdentifier): array
    {
        if (!$this->apiClient->isTestMode()) {
            throw NESException::invalidRequest("Test verisi sadece test modunda kullanılabilir");
        }

        return [
            'customer_identifier' => $customerIdentifier,
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'currency' => 'TRY',
            'exchange_rate' => 1.0,
            'items' => [
                [
                    'name' => 'Test Ürün 1',
                    'description' => 'Test ürün açıklaması',
                    'quantity' => 2,
                    'unit' => 'ADET',
                    'unit_price' => 100.00,
                    'tax_rate' => 18.0,
                    'discount_rate' => 0.0
                ],
                [
                    'name' => 'Test Ürün 2',
                    'description' => 'Test ürün açıklaması 2',
                    'quantity' => 1,
                    'unit' => 'ADET',
                    'unit_price' => 250.00,
                    'tax_rate' => 18.0,
                    'discount_rate' => 10.0
                ]
            ],
            'notes' => 'Test faturası - Otomatik oluşturuldu',
            'is_test' => true
        ];
    }
}
