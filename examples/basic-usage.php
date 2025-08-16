<?php
/**
 * NES Fatura Entegrasyonu - Temel Kullanım Örnekleri
 * 
 * Bu dosya, NES Fatura sistemi ile entegrasyon için temel kullanım örneklerini içerir.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use NES\Service\CustomerService;
use NES\Service\InvoiceService;
use NES\Exception\NESException;

// Hata raporlamayı etkinleştir
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== NES Fatura Entegrasyonu - Temel Kullanım Örnekleri ===\n\n";

try {
    // Servisleri başlat
    $customerService = new CustomerService();
    $invoiceService = new InvoiceService();
    
    echo "✅ Servisler başarıyla başlatıldı\n\n";
    
    // Örnek 1: TCSI ile müşteri sorgulama
    echo "1️⃣ TCSI ile Müşteri Sorgulama\n";
    echo "--------------------------------\n";
    
    $tcsi = '12345678901';
    echo "TCSI: {$tcsi}\n";
    
    if ($customerService->validateIdentifier($tcsi)) {
        echo "✅ TCSI formatı geçerli\n";
        
        try {
            $status = $customerService->checkCustomerStatus($tcsi);
            echo "✅ Müşteri durumu alındı:\n";
            echo "   - E-Fatura: " . ($status['has_e_fatura'] ? 'Evet' : 'Hayır') . "\n";
            echo "   - E-Arşiv: " . ($status['has_e_arsiv'] ? 'Evet' : 'Hayır') . "\n";
            echo "   - Son Kontrol: {$status['last_check']}\n";
        } catch (NESException $e) {
            echo "❌ Müşteri durumu alınamadı: {$e->getMessage()}\n";
        }
    } else {
        echo "❌ TCSI formatı geçersiz\n";
    }
    
    echo "\n";
    
    // Örnek 2: VKN ile müşteri sorgulama
    echo "2️⃣ VKN ile Müşteri Sorgulama\n";
    echo "--------------------------------\n";
    
    $vkn = '1234567890';
    echo "VKN: {$vkn}\n";
    
    if ($customerService->validateIdentifier($vkn)) {
        echo "✅ VKN formatı geçerli\n";
        
        try {
            $status = $customerService->checkCustomerStatus($vkn);
            echo "✅ Müşteri durumu alındı:\n";
            echo "   - E-Fatura: " . ($status['has_e_fatura'] ? 'Evet' : 'Hayır') . "\n";
            echo "   - E-Arşiv: " . ($status['has_e_arsiv'] ? 'Evet' : 'Hayır') . "\n";
            echo "   - Son Kontrol: {$status['last_check']}\n";
        } catch (NESException $e) {
            echo "❌ Müşteri durumu alınamadı: {$e->getMessage()}\n";
        }
    } else {
        echo "❌ VKN formatı geçersiz\n";
    }
    
    echo "\n";
    
    // Örnek 3: Test müşteri verisi alma
    echo "3️⃣ Test Müşteri Verisi Alma\n";
    echo "--------------------------------\n";
    
    try {
        $testCustomer = $customerService->getTestCustomerData($tcsi);
        echo "✅ Test müşteri verisi alındı:\n";
        echo "   - Ad: {$testCustomer['name']}\n";
        echo "   - Tip: {$testCustomer['type']}\n";
        echo "   - E-posta: {$testCustomer['email']}\n";
        echo "   - Test Modu: " . ($testCustomer['is_test'] ? 'Evet' : 'Hayır') . "\n";
    } catch (NESException $e) {
        echo "❌ Test müşteri verisi alınamadı: {$e->getMessage()}\n";
    }
    
    echo "\n";
    
    // Örnek 4: Test fatura verisi alma
    echo "4️⃣ Test Fatura Verisi Alma\n";
    echo "--------------------------------\n";
    
    try {
        $testInvoice = $invoiceService->getTestInvoiceData($tcsi);
        echo "✅ Test fatura verisi alındı:\n";
        echo "   - Müşteri: {$testInvoice['customer_identifier']}\n";
        echo "   - Fatura Tarihi: {$testInvoice['invoice_date']}\n";
        echo "   - Vade Tarihi: {$testInvoice['due_date']}\n";
        echo "   - Para Birimi: {$testInvoice['currency']}\n";
        echo "   - Kalem Sayısı: " . count($testInvoice['items']) . "\n";
        echo "   - Test Modu: " . ($testInvoice['is_test'] ? 'Evet' : 'Hayır') . "\n";
        
        // Fatura kalemlerini göster
        echo "   - Kalemler:\n";
        foreach ($testInvoice['items'] as $index => $item) {
            echo "     " . ($index + 1) . ". {$item['name']} - {$item['quantity']} {$item['unit']} x {$item['unit_price']} TL\n";
        }
    } catch (NESException $e) {
        echo "❌ Test fatura verisi alınamadı: {$e->getMessage()}\n";
    }
    
    echo "\n";
    
    // Örnek 5: E-Fatura oluşturma (test)
    echo "5️⃣ E-Fatura Oluşturma (Test)\n";
    echo "--------------------------------\n";
    
    try {
        // Önce müşteri durumunu kontrol et
        $customerStatus = $customerService->checkCustomerStatus($tcsi);
        
        if ($customerStatus['has_e_fatura']) {
            echo "✅ Müşteri e-fatura kullanıcısı\n";
            
            // Test fatura verisi ile e-fatura oluştur
            $invoiceData = $invoiceService->getTestInvoiceData($tcsi);
            $result = $invoiceService->createEInvoice($invoiceData);
            
            echo "✅ E-Fatura oluşturuldu:\n";
            echo "   - Fatura ID: " . ($result['invoice_id'] ?? 'N/A') . "\n";
            echo "   - Durum: " . ($result['status'] ?? 'N/A') . "\n";
        } else {
            echo "❌ Müşteri e-fatura kullanıcısı değil\n";
        }
    } catch (NESException $e) {
        echo "❌ E-Fatura oluşturulamadı: {$e->getMessage()}\n";
    }
    
    echo "\n";
    
    // Örnek 6: E-Arşiv faturası oluşturma (test)
    echo "6️⃣ E-Arşiv Faturası Oluşturma (Test)\n";
    echo "----------------------------------------\n";
    
    try {
        // Önce müşteri durumunu kontrol et
        $customerStatus = $customerService->checkCustomerStatus($tcsi);
        
        if ($customerStatus['has_e_arsiv']) {
            echo "✅ Müşteri e-arşiv kullanıcısı\n";
            
            // Test fatura verisi ile e-arşiv faturası oluştur
            $invoiceData = $invoiceService->getTestInvoiceData($tcsi);
            $result = $invoiceService->createEArchiveInvoice($invoiceData);
            
            echo "✅ E-Arşiv faturası oluşturuldu:\n";
            echo "   - Fatura ID: " . ($result['invoice_id'] ?? 'N/A') . "\n";
            echo "   - Durum: " . ($result['status'] ?? 'N/A') . "\n";
        } else {
            echo "❌ Müşteri e-arşiv kullanıcısı değil\n";
        }
    } catch (NESException $e) {
        echo "❌ E-Arşiv faturası oluşturulamadı: {$e->getMessage()}\n";
    }
    
    echo "\n";
    
    // Örnek 7: Hata yönetimi
    echo "7️⃣ Hata Yönetimi Örnekleri\n";
    echo "-----------------------------\n";
    
    // Geçersiz TCSI formatı
    $invalidTcsi = '12345';
    echo "Geçersiz TCSI testi: {$invalidTcsi}\n";
    
    try {
        $customerService->checkCustomerStatus($invalidTcsi);
    } catch (NESException $e) {
        echo "✅ Hata yakalandı: {$e->getMessage()}\n";
        echo "   - Hata Kodu: " . ($e->getErrorCode() ?? 'N/A') . "\n";
    }
    
    echo "\n";
    
    echo "=== Tüm örnekler tamamlandı ===\n";
    
} catch (Exception $e) {
    echo "❌ Genel hata: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}
